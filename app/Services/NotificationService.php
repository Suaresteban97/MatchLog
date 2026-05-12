<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotificationService
{
    /**
     * All supported notification types and their message templates.
     * {sender} is replaced by the sender's name.
     */
    private const MESSAGES = [
        'post_like'              => '{sender} le dio me gusta a tu publicación',
        'post_comment'           => '{sender} comentó en tu publicación',
        'comment_like'           => '{sender} le dio me gusta a tu comentario',
        'comment_reply'          => '{sender} respondió a tu comentario',
        'follow'                 => '{sender} comenzó a seguirte',
        'friend_request'         => '{sender} te envió una solicitud de amistad',
        'friend_accepted'        => '{sender} aceptó tu solicitud de amistad',
        'session_join'           => '{sender} se unió a tu sesión de juego',
        'session_request'        => '{sender} quiere unirse a tu sesión de juego',
        'contribution_resolved'  => 'Tu contribución fue {status}',
    ];

    /**
     * Create a notification.
     *
     * @param int        $userId     Who receives the notification
     * @param int        $senderId   Who triggered the notification
     * @param string     $type       One of the MESSAGES keys
     * @param Model|null $notifiable The related entity (Post, PostComment, Friendship, etc.)
     * @param array      $extra      Extra placeholders for the message template
     */
    public function send(int $userId, ?int $senderId, string $type, ?Model $notifiable = null, array $extra = []): ?Notification
    {
        // Don't notify yourself
        if ($senderId !== null && $userId === $senderId) {
            return null;
        }

        $senderName = 'Sistema';
        if ($senderId !== null) {
            $sender = User::find($senderId);
            if (!$sender) {
                return null;
            }
            $senderName = $sender->name;
        }

        $message = $this->buildMessage($type, $senderName, $extra);

        $data = [
            'user_id'   => $userId,
            'sender_id' => $senderId,
            'type'      => $type,
            'message'   => $message,
        ];

        if ($notifiable) {
            $data['notifiable_type'] = get_class($notifiable);
            $data['notifiable_id']   = $notifiable->getKey();
        }

        $notification = Notification::create($data);

        // Broadcast real-time event via WebSocket
        broadcast(new NotificationCreated($notification));

        return $notification;
    }

    /**
     * Get paginated notifications for a user.
     */
    public function getForUser(int $userId, int $perPage = 20)
    {
        return Notification::forUser($userId)
            ->with(['sender:id,name,email', 'notifiable'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Count unread notifications for a user.
     */
    public function unreadCount(int $userId): int
    {
        return Notification::forUser($userId)->unread()->count();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Build the human-readable message from a template.
     */
    private function buildMessage(string $type, string $senderName, array $extra = []): string
    {
        $template = self::MESSAGES[$type] ?? 'Tienes una nueva notificación';

        $replacements = array_merge(['sender' => $senderName], $extra);

        $message = $template;
        foreach ($replacements as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }
}
