<?php

namespace App\Models; // Ensure correct namespace

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // For generating slugs

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    /**
     * Boot method to handle model events.
     * Automatically generate a slug when a category is being created or its name is changed.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
                // Ensure slug is unique
                $originalSlug = $category->slug;
                $count = 1;
                while (static::where('slug', $category->slug)->exists()) {
                    $category->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->getOriginal('slug'))) { // Or if you want to regenerate slug on name change always
                $category->slug = Str::slug($category->name);
                // Ensure slug is unique, ignoring self
                $originalSlug = $category->slug;
                $count = 1;
                $id = $category->id;
                while (static::where('slug', $category->slug)->where('id', '!=', $id)->exists()) {
                    $category->slug = "{$originalSlug}-{$count}";
                    $count++;
                }
            }
        });
    }

    /**
     * Accessor for the category image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_path) {
            $disk = config('filesystems.default_public_disk', 'public');
            try {
                return Storage::disk($disk)->url($this->image_path);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error generating image URL for category {$this->id}: " . $e->getMessage());
                return $this->getDefaultImageUrl();
            }
        }
        return $this->getDefaultImageUrl();
    }

    protected function getDefaultImageUrl(): string
    {
        return asset('images/default-category.png'); // Add this default image
    }

    /**
     * Relationship to the Store model.
     * A category belongs to a store.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relationship to itself for parent category.
     * A category can have one parent.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relationship to itself for child categories.
     * A category can have many children (sub-categories).
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Relationship to Products.
     * A category can have many products.
     */
    // public function products()
    // {
    //     return $this->hasMany(Product::class);
    // }
}