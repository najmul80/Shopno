<?php

namespace App\Http\Controllers\Api; // Ensure correct namespace

use App\Models\Category;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Services\FileStorageService; // For image uploads
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // To get authenticated user
use Illuminate\Support\Facades\Log;
use Exception;

class CategoryController extends BaseApiController
{
    protected FileStorageService $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;

        $this->middleware('auth:api');
        // Permissions should be defined in RolesAndPermissionsSeeder
        $this->middleware('permission:view categories')->only(['index', 'show']);
        $this->middleware('permission:create categories')->only(['store']);
        $this->middleware('permission:update categories')->only(['update']);
        $this->middleware('permission:delete categories')->only(['destroy']);
        // Super admin might have 'manage categories_all' permission
    }

    /**
     * Display a listing of categories, scoped to the user's store or all if super-admin.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Category::query()->with(['store', 'parent', 'children']); // Eager load relationships

            if ($user->hasRole('super-admin') && $request->has('store_id_filter')) {
                // Super admin can filter by store_id
                $query->where('store_id', $request->store_id_filter);
            } elseif (!$user->hasRole('super-admin') && $user->store_id) {
                // Other users see categories of their own store
                $query->where('store_id', $user->store_id);
            } elseif (!$user->hasRole('super-admin') && !$user->store_id) {
                // User not super-admin and not assigned to any store - cannot see any store-specific categories
                return $this->forbiddenResponse('You are not assigned to a store to view categories.');
            }
            // If super-admin and no store_id_filter, they see all categories (consider implications)
            // or you might want to force super-admin to always filter by a store for this endpoint.

            // Further filtering options
            if ($request->filled('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->filled('parent_id')) {
                if (strtolower($request->parent_id) === 'null' || $request->parent_id === '0') {
                    $query->whereNull('parent_id'); // Top-level categories
                } else {
                    $query->where('parent_id', $request->parent_id); // Specific parent
                }
            }
            if ($request->filled('search')) {
                $query->where('name', 'like', "%{$request->search}%");
            }

            $categories = $query->orderBy('sort_order')->orderBy('name')
                ->paginate($request->input('per_page', 15));

            return CategoryResource::collection($categories);
        } catch (Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return $this->errorResponse('Could not fetch categories.', 500);
        }
    }

    /**
     * Store a newly created category in storage.
     * @param  \App\Http\Requests\Category\StoreCategoryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $user = Auth::user();
            $validatedData = $request->validated();

            // Determine store_id
            if ($user->hasRole('super-admin') && isset($validatedData['store_id'])) {
                $validatedData['store_id'] = $validatedData['store_id'];
            } elseif (!$user->hasRole('super-admin') && $user->store_id) {
                $validatedData['store_id'] = $user->store_id;
            } else {
                // If super-admin didn't provide store_id, or user has no store_id
                return $this->errorResponse('Store ID is required or user is not associated with a store.', 422);
            }

            if ($request->hasFile('image')) {
                $imagePath = $this->fileStorageService->store($request->file('image'), 'category-images');
                if ($imagePath) {
                    $validatedData['image_path'] = $imagePath;
                }
            }
            if (isset($validatedData['image'])) unset($validatedData['image']);


            $category = Category::create($validatedData);

            activity()->causedBy($user)->performedOn($category)->log('Category created: ' . $category->name);

            return $this->successResponse(
                new CategoryResource($category->load(['store', 'parent', 'children'])),
                'Category created successfully.',
                201
            );
        } catch (Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage(), ['request_data' => $request->except('image')]);
            return $this->errorResponse('Could not create category. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified category.
     * @param  \App\Models\Category  $category (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Category $category)
    {
        try {
            $user = Auth::user();
            // Authorization: User can see categories of their own store, or super-admin can see any.
            if (!$user->hasRole('super-admin') && $user->store_id !== $category->store_id) {
                return $this->forbiddenResponse('You do not have permission to view this category.');
            }

            return $this->successResponse(
                new CategoryResource($category->load(['store', 'parent', 'children'])),
                'Category details fetched successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error fetching category details for ID {$category->id}: " . $e->getMessage());
            return $this->errorResponse('Could not fetch category details.', 500);
        }
    }

    /**
     * Update the specified category in storage.
     * @param  \App\Http\Requests\Category\UpdateCategoryRequest  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        try {
            $user = Auth::user();
            // Authorization: User can update categories of their own store, or super-admin can update any.
            if (!$user->hasRole('super-admin') && $user->store_id !== $category->store_id) {
                return $this->forbiddenResponse('You do not have permission to update this category.');
            }

            $validatedData = $request->validated();

            if ($request->hasFile('image')) {
                if ($category->image_path) {
                    $this->fileStorageService->delete($category->image_path);
                }
                $imagePath = $this->fileStorageService->store($request->file('image'), 'category-images');
                if ($imagePath) {
                    $validatedData['image_path'] = $imagePath;
                }
            }
            if (isset($validatedData['image'])) unset($validatedData['image']);

            // Prevent a category from becoming its own child or descendant (more complex logic needed for full tree check)
            if (isset($validatedData['parent_id']) && $validatedData['parent_id'] == $category->id) {
                return $this->errorResponse('A category cannot be its own parent.', 422);
            }
            // A full check to prevent circular dependencies (A->B->C->A) is more involved.

            $category->update($validatedData);

            activity()->causedBy($user)->performedOn($category)->log('Category updated: ' . $category->name);

            return $this->successResponse(
                new CategoryResource($category->fresh()->load(['store', 'parent', 'children'])),
                'Category updated successfully.'
            );
        } catch (Exception $e) {
            Log::error("Error updating category ID {$category->id}: " . $e->getMessage(), ['request_data' => $request->except('image')]);
            return $this->errorResponse('Could not update category. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified category from storage (soft delete).
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Category $category)
    {
        try {
            $user = Auth::user();
            // Authorization: User can delete categories of their own store, or super-admin can delete any.
            if (!$user->hasRole('super-admin') && $user->store_id !== $category->store_id) {
                return $this->forbiddenResponse('You do not have permission to delete this category.');
            }

            // Note: Soft deleting a parent category with onDelete('cascade') for children
            // might not soft delete children unless the children also use SoftDeletes and
            // you handle this cascading logic in an observer or similar.
            // For now, direct soft delete.
            $categoryName = $category->name;
            $category->delete(); // Soft delete

            activity()->causedBy($user)->log('Category soft-deleted: ' . $categoryName . ' (ID: ' . $category->id . ')');

            return $this->successResponse(null, 'Category soft-deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error soft-deleting category ID {$category->id}: " . $e->getMessage());
            return $this->errorResponse('Could not delete category.', 500);
        }
    }
}
