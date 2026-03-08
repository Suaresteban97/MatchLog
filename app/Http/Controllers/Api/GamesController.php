<?php

namespace App\Http\Controllers\Api;

use App\Services\GameService;
use App\Models\Game;
use App\Models\GameStatus;
use App\Models\GamePlatform;
use App\Http\Resources\Games\GameResource;
use App\Http\Resources\Games\GameDetailResource;
use App\Http\Requests\Games\StoreGameRequest;
use App\Http\Requests\Games\UpdateGameRequest;
use App\Http\Requests\Games\ToggleUserGameRequest;
use App\Http\Requests\Games\ChangeUserGameStatusRequest;
use App\Http\Requests\Games\UpdateUserGameRequest;
use App\Http\Resources\Games\UserGameResource;
use Illuminate\Http\Request;

class GamesController extends Controller
{
    protected $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    /**
     * Display a paginated list of games with optional filters.
     * GET /api/games?name=Zelda&platform=Switch...
     */
    public function getGames(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $games = $this->gameService->getGames($request->all(), $perPage);

        return GameResource::collection($games);
    }

    /**
     * Display games linked to the authenticated user.
     * GET /api/my-games
     */
    public function getUserGames(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $userGames = $this->gameService->getUserGames($request->user(), $perPage);

        return UserGameResource::collection($userGames);
    }

    /**
     * Display a specific game with all relations.
     * GET /api/games/{id}
     */
    public function show($id)
    {
        $game = $this->gameService->getGameWithRelations($id);

        return new GameDetailResource($game);
    }

    /**
     * Store a new game in the catalog.
     * POST /api/games
     */
    public function store(StoreGameRequest $request)
    {
        $game = $this->gameService->storeGame($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Juego creado correctamente.',
            'data' => $game
        ], 201);
    }

    /**
     * Update a specific game.
     * PUT /api/games/{game}
     */
    public function update(UpdateGameRequest $request, Game $game)
    {
        $updatedGame = $this->gameService->updateGame($game, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Juego actualizado correctamente.',
            'data' => $updatedGame
        ]);
    }

    /**
     * Link or unlink a game to/from the authenticated user's library.
     * POST /api/my-games/{id}/toggle
     */
    public function toggleUserGame(ToggleUserGameRequest $request, $id)
    {
        $result = $this->gameService->toggleUserGame($request->user(), $id, $request->validated());

        return response()->json([
            'success' => true,
            'action' => $result['action'],
            'message' => $result['message']
        ]);
    }

    /**
     * Change the status of a game in the user's library.
     * PATCH /api/my-games/{id}/status
     */
    public function changeStatus(ChangeUserGameStatusRequest $request, $id)
    {
        $result = $this->gameService->changeUserGameStatus($request->user(), $id, $request->validated()['status']);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status_name' => $result['status'],
            'message' => $result['message']
        ]);
    }
    /**
     * Update the user's personal game data in their library.
     * PUT /api/my-games/{id}
     * The game MUST already be in the user's library.
     */
    public function updateUserGame(UpdateUserGameRequest $request, $id)
    {
        $result = $this->gameService->updateUserGame($request->user(), $id, $request->validated());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Datos del juego actualizados correctamente.',
            'data' => $result['data']
        ]);
    }

    /**
     * Get metadata for games (statuses and platforms).
     * GET /api/games-metadata
     */
    public function metadata()
    {
        return response()->json([
            'statuses' => GameStatus::all(),
            'platforms' => GamePlatform::all()
        ]);
    }
}
