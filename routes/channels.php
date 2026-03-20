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

    return false;
});
