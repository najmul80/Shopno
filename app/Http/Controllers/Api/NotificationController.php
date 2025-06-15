<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification; // For interacting with specific notifications

class NotificationController extends BaseApiController
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get all (or unread) notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $limit = $request->input('limit', 15); // How many notifications to fetch
        $onlyUnread = filter_var($request->input('unread_only', false), FILTER_VALIDATE_BOOLEAN);

        if ($onlyUnread) {
            $notifications = $user->unreadNotifications()->latest()->paginate($limit);
        } else {
            $notifications = $user->notifications()->latest()->paginate($limit);
        }

        // The default notification collection doesn't use resources well.
        // You might want to manually format it or create a NotificationResource.
        return $this->successResponse($notifications, 'Notifications fetched successfully.');
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return $this->notFoundResponse('Notification not found.');
        }

        if (!$notification->read_at) { // Mark as read only if it's unread
            $notification->markAsRead();
        }

        return $this->successResponse(null, 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead(); // Marks all unread as read

        return $this->successResponse(null, 'All unread notifications marked as read.');
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Request $request, $notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return $this->notFoundResponse('Notification not found.');
        }

        $notification->delete();
        return $this->successResponse(null, 'Notification deleted successfully.');
    }

    /**
     * Get user's notification settings (Placeholder - needs implementation).
     * This would involve a new table/model for user_notification_preferences.
     */
    public function getSettings(Request $request)
    {
        // Fetch user's notification preferences from a dedicated table/model
        // e.g., $settings = Auth::user()->notificationSettings;
        return $this->successResponse(['email_low_stock' => true, 'in_app_new_sale' => true], 'Notification settings fetched (placeholder).');
    }

    /**
     * Update user's notification settings (Placeholder - needs implementation).
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'email_low_stock' => 'sometimes|boolean',
            'in_app_new_sale' => 'sometimes|boolean',
            // Add other settings
        ]);

        // Update user's notification preferences in the database
        // Auth::user()->notificationSettings()->updateOrCreate([...], $validated);

        return $this->successResponse($validated, 'Notification settings updated (placeholder).');
    }
}