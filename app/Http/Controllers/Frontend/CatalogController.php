<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\GameService;
use App\Http\Resources\Games\GameResource;
use App\Http\Resources\Games\GameDetailResource;
use App\Models\Genre;
use App\Models\GamePlatform;

class CatalogController extends Controller
{
    protected GameService $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 24);
        $paginator = $this->gameService->getGames($request->all(), $perPage);

        $gamesData = GameResource::collection($paginator)->response()->getData(true);

        $filterKeys = [
            'name', 'genre_id', 'platform_id', 'platform',
            'developer', 'publisher', 'release_year',
            'metacritic_score', 'sort_by', 'sort_dir',
            'is_multiplayer', 'is_cooperative',
            'is_online_multiplayer', 'is_local_multiplayer',
        ];

        return Inertia::render('Frontend/Games/Index', [
            'initialGames' => $gamesData,
            'filters'      => $request->only($filterKeys),
            'genres'       => Genre::orderBy('name')->get(['id', 'name']),
            'platforms'    => GamePlatform::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show($identifier)
    {
        // $identifier can be the slug or numeric ID
        $game = $this->gameService->getGameWithRelations($identifier);
        
        return Inertia::render('Frontend/Games/Show', [
            'game' => new GameDetailResource($game)
        ]);
    }
}
