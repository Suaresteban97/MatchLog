<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'reply_to_id',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * The conversation that this message belongs to.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * The user who sent the message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * The message this one is replying to.
     */
    public function repliedMessage()
    {
        return $this->belongsTo(DirectMessage::class, 'reply_to_id');
    }

    /**
     * The replies to this message.
     */
    public function replies()
    {
        return $this->hasMany(DirectMessage::class, 'reply_to_id');
    }
}
