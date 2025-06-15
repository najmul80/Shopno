<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Spatie\Activitylog\Models\Activity; // Activity model from Spatie package
use Illuminate\Http\Request;
use App\Http\Resources\ActivityLogResource; // একটি রিসোর্স তৈরি করতে হবে

class ActivityLogController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'permission:view activity_log']); // একটি নতুন পারমিশন
    }

    public function index(Request $request)
    {
        $query = Activity::query()->with(['causer', 'subject']); // Eager load causer and subject

        // Filtering examples
        if ($request->filled('log_name')) { // default, App\Models\User etc.
            $query->where('log_name', $request->log_name);
        }
        if ($request->filled('description')) {
            $query->where('description', 'like', "%{$request->description}%");
        }
        if ($request->filled('causer_id') && $request->filled('causer_type')) {
            $query->where('causer_id', $request->causer_id)
                  ->where('causer_type', 'App\\Models\\' . ucfirst($request->causer_type)); // e.g., App\Models\User
        }
        if ($request->filled('subject_id') && $request->filled('subject_type')) {
            $query->where('subject_id', $request->subject_id)
                  ->where('subject_type', 'App\\Models\\' . ucfirst($request->subject_type)); // e.g., App\Models\Product
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . " 00:00:00", $request->end_date . " 23:59:59"]);
        }


        $activities = $query->latest()->paginate($request->input('per_page', 20));

        // return ActivityLogResource::collection($activities); // Use a resource
        return $this->successResponse($activities, 'Activity logs fetched successfully.'); // Or direct for now
    }
}