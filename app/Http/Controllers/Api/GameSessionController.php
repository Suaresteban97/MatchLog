<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GameSessionService;
use App\Http\Requests\GameSession\StoreGameSessionRequest;
use App\Http\Requests\GameSession\UpdateGameSessionRequest;
use Illuminate\Http\Request;

class GameSessionController extends Controller
{
    protected $sessionService;

    public function __construct(GameSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Display a listing of game sessions (hosted by user + participating).
     */
    public function index(Request $request)
    {
        $hosting = $this->sessionService->getHostingSessions($request->user());
        $participating = $this->sessionService->getParticipatingSessions($request->user());

        return response()->json([
            'hosting' => $hosting,
            'participating' => $participating
        ], 200);
    }

    /**
     * Store a newly created game session.
     */
    public function store(StoreGameSessionRequest $request)
    {
        $session = $this->sessionService->createSession($request->user(), $request->validated());

        return response()->json(['session' => $session], 201);
    }

    /**
     * Display the specified game session.
     */
    public function show(Request $request, $id)
    {
        try {
            $session = $this->sessionService->getSession($id);
            return response()->json(['session' => $session], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Sesión no encontrada'], 404);
        }
    }

    /**
     * Update the specified game session (only host can update).
     */
    public function update(UpdateGameSessionRequest $request, $id)
    {
        try {
            $session = $this->sessionService->updateSession($request->user(), $id, $request->validated());
            return response()->json(['session' => $session], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Sesión no encontrada o no eres el anfitrión'], 404);
        }
    }

    /**
     * Delete the game session (only host can delete).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->sessionService->deleteSession($request->user(), $id);
            return response()->json(['message' => 'Sesión eliminada correctamente'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Sesión no encontrada o no eres el anfitrión'], 404);
        }
    }

    /**
     * Join a game session.
     */
    public function join(Request $request, $id)
    {
        try {
            $result = $this->sessionService->joinSession($request->user(), $id);

            if (isset($result['status']) && $result['status'] === 'error') {
                return response()->json(['error' => $result['message']], $result['code']);
            }

            $status = isset($result['status']) && $result['status'] === 'success' ? 201 : 200;

            return response()->json(['message' => $result['message']], $status);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Sesión no encontrada'], 404);
        }
    }

    /**
     * Leave a game session.
     * If the user is the host, the entire session is deleted.
     */
    public function leave(Request $request, $id)
    {
        try {
            $result = $this->sessionService->leaveSession($request->user(), $id);
            return response()->json(['message' => $result['message']], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'No participas en esta sesión'], 404);
        }
    }

    public function handleRequest(Request $request, $id, $userId)
    {
        $action = $request->input('action'); // 'accept' or 'reject'
        if (!in_array($action, ['accept', 'reject'])) {
            return response()->json(['message' => 'Acción inválida'], 400);
        }

        try {
            $result = $this->sessionService->handleParticipantRequest($request->user(), $id, $userId, $action);
            if (isset($result['status']) && $result['status'] === 'error') {
                return response()->json(['message' => $result['message']], 400);
            }
            return response()->json(['message' => $result['message']], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Sesión o solicitud no encontrada'], 404);
        }
    }

    /**
     * List all open/public game sessions.
     */
    public function browse(Request $request)
    {
        $filters = $request->only(['game_id', 'start_date', 'end_date', 'available_only', 'search']);
        $sessions = $this->sessionService->browseSessions($request->user(), $filters);
        return response()->json($sessions, 200);
    }
}
