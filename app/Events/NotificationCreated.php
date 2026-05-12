<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->notification->user_id),
        ];
    }

    /**
     * Define the data to be broadcasted with the event.
     */
    public function broadcastWith(): array
    {
        return [
            'id'         => $this->notification->id,
            'type'       => $this->notification->type,
            'message'    => $this->notification->message,
            'sender'     => $this->notification->sender ? [
                'id'   => $this->notification->sender->id,
                'name' => $this->notification->sender->name,
            ] : null,
            'notifiable_type' => $this->notification->notifiable_type,
            'notifiable_id'   => $this->notification->notifiable_id,
            'notifiable'      => $this->notification->notifiable,
            'read_at'    => $this->notification->read_at,
            'created_at' => $this->notification->created_at->toIso8601String(),
        ];
    }
}
