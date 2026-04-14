<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('session.{sessionId}', function ($user, $sessionId) {
    $session = \App\Models\GameSession::find($sessionId);

    if ($session && ($session->host_id === $user->id || $session->participants()->where('user_id', $user->id)->exists())) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

});

// Private channel for 1-v-1 chat
Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    
    if ($conversation && ($conversation->user1_id === $user->id || $conversation->user2_id === $user->id)) {
        return true;
    }
    
    return false;
});

// Global presence channel for online status
Broadcast::channel('global', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
    return false;
});
