<?php
namespace App\Notifications\Auth; // Corrected namespace

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public User $newUser;

    public function __construct(User $newUser)
    {
        $this->newUser = $newUser;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Notify super admins
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('New User Registration: ' . $this->newUser->name)
                    ->greeting("Hello {$notifiable->name},")
                    ->line("A new user has registered on the platform:")
                    ->line("Name: {$this->newUser->name}")
                    ->line("Email: {$this->newUser->email}")
                    ->lineIf($this->newUser->store_id, "Assigned to Store ID: {$this->newUser->store_id}")
                    ->action('View User Profile', url('/admin/users/' . $this->newUser->id)) // Placeholder admin URL
                    ->line('You might want to review their details or assigned role.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->newUser->id,
            'user_name' => $this->newUser->name,
            'user_email' => $this->newUser->email,
            'message' => "New user '{$this->newUser->name}' registered.",
        ];
    }
}