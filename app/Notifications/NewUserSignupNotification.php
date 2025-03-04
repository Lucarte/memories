<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewUserSignupNotification extends Notification
{
    use Queueable;

    public $user;

    /**
     * Create a new notification instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Generate the URL for the approval endpoint
        $approveUrl = url('api/approve-user/' . $this->user->id);  // This will generate /api/approve-user/{userId}
        
        return (new MailMessage)
                    ->subject('New User Signup Approval Needed')
                    ->line('A new user has signed up and is awaiting approval.')
                    ->line('User Email: ' . $this->user->email)
                    ->line('Click below to approve the user:')
                    ->line(
                        `<form action="' . $approveUrl . '" method="POST">
                            <button type="submit">Approve User</button>
                        </form>`
                    ) // Manually create the form to send a POST request
                    ->line('Good Job, Mammut!');
    }
}
