<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    /**
     * Get paginated notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);

        $notifications = $this->notificationService->getForUser(
            $request->user()->id,
            $perPage
        );

        return response()->json([
            'success'      => true,
            'data'         => $notifications->items(),
            'unread_count' => $this->notificationService->unreadCount($request->user()->id),
            'pagination'   => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    /**
     * Get unread notifications count (lightweight endpoint for badge).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->unreadCount($request->user()->id);

        return response()->json([
            'success'      => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $marked = $this->notificationService->markAsRead($id, $request->user()->id);

        if (!$marked) {
            return response()->json(['success' => false, 'message' => 'Notificación no encontrada'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Notificación marcada como leída']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => "{$count} notificaciones marcadas como leídas",
        ]);
    }
}
