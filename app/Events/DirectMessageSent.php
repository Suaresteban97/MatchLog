<?php

namespace App\Events;

use App\Models\DirectMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(DirectMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Using a PrivateChannel so only participants can listen,
        // or a PresenceChannel if we want to know if the other person is "in the chat window" currently.
        // For general "Online" we use global, but for the actual chat, a Private Channel is enough and secure.
        $conversation = \App\Models\Conversation::find($this->message->conversation_id);
        $receiverId = $conversation->user1_id === $this->message->sender_id ? $conversation->user2_id : $conversation->user1_id;

        return [
            new PrivateChannel('chat.conversation.' . $this->message->conversation_id),
            new PrivateChannel('App.Models.User.' . $receiverId),
        ];
    }

    /**
     * Define the data to be broadcasted with the event.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message,
            'reply_to_id' => $this->message->reply_to_id,
            'created_at' => $this->message->created_at->toIso8601String(),
            'reply_context' => $this->message->reply_to_id && $this->message->repliedMessage ? [
                'id' => $this->message->repliedMessage->id,
                'message' => $this->message->repliedMessage->message,
                'sender_id' => $this->message->repliedMessage->sender_id,
            ] : null,
        ];
    }
}
