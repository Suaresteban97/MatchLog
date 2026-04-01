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

        // Convert paginator items using the Resource, keeping the paginator structure
        // Since Inertia handles the top level array, we can use Laravel's built in array casting
        $gamesData = GameResource::collection($paginator)->response()->getData(true);

        return Inertia::render('Frontend/Games/Index', [
            'initialGames' => $gamesData,
            'filters' => $request->only(['name', 'genre_id', 'platform', 'metacritic_score']),
            'genres' => Genre::orderBy('name')->get(),
            'platforms' => GamePlatform::orderBy('name')->get()
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
