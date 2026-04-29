<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Game;
use App\Models\UserGame;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Posts\StorePostRequest;

class PostController extends Controller
{
    public function index()
    {
        // Get feed with relations
        $posts = Post::with([
            'user.socialProfiles.socialPlatform', 
            'game', 
            'collection', 
            'gameSession.host', 
            'gameSession.participants',
            'userDevice.device', 
            'userDevice.characteristics'
        ])
        ->latest()
        ->paginate(15);
        
        return response()->json($posts);
    }

    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();

        $post = $request->user()->posts()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $post->load([
                'user.socialProfiles.socialPlatform', 
                'game', 
                'collection', 
                'gameSession', 
                'userDevice.device', 
                'userDevice.characteristics'
            ])
        ]);
    }

    public function suggestions(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);
        
        // Get user's top genres based on their games
        $topGenreIds = DB::table('user_games')
            ->join('game_genre', 'user_games.game_id', '=', 'game_genre.game_id')
            ->where('user_games.user_id', $user->id)
            ->select('game_genre.genre_id', DB::raw('count(*) as total'))
            ->groupBy('game_genre.genre_id')
            ->orderByDesc('total')
            ->limit(3)
            ->pluck('genre_id');

        if ($topGenreIds->isEmpty()) {
            // Fallback if no games: high metacritic score
            $suggestions = Game::with('genres', 'platforms')
                ->orderByDesc('metacritic_score')
                ->paginate($perPage);
        } else {
            // Get games from top genres that the user doesn't have
            $userGameIds = UserGame::where('user_id', $user->id)->pluck('game_id');
            
            $suggestions = Game::with('genres', 'platforms')
                ->whereHas('genres', function($q) use ($topGenreIds) {
                    $q->whereIn('genres.id', $topGenreIds);
                })
                ->whereNotIn('id', $userGameIds)
                ->orderByDesc('metacritic_score')
                ->paginate($perPage);
        }

        return response()->json($suggestions);
    }

    public function recentGames(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $oneMonthAgo = now()->subMonth()->startOfDay();
        $oneMonthAhead = now()->addMonth()->endOfDay();

        $recentGames = Game::with('genres', 'platforms')
            ->whereNotNull('release_date')
            ->whereBetween('release_date', [$oneMonthAgo, $oneMonthAhead])
            ->orderByDesc('release_date')
            ->paginate($perPage);
            
        return response()->json($recentGames);
    }
}
