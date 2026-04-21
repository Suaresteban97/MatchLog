<?php

namespace App\Http\Controllers\Api;

use App\Models\Collection;
use App\Models\Game;
use App\Http\Requests\Collections\StoreCollectionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    /**
     * Get all collections for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $collections = $request->user()
            ->collections()
            ->withCount('games') // Useful for showing "X games" in the list
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $collections,
        ]);
    }

    /**
     * Create a new collection.
     */
    public function store(StoreCollectionRequest $request): JsonResponse
    {
        $collection = $request->user()->collections()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Colección creada con éxito.',
            'data'    => $collection,
        ], 201);
    }

    /**
     * Show a specific collection with its games.
     */
    public function show(Request $request, Collection $collection): JsonResponse
    {
        // Simple authorization
        if ($collection->user_id !== $request->user()->id && !$collection->is_public) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $collection->load(['games' => function ($query) {
            // Sort by recently added
            $query->orderBy('collection_game.created_at', 'desc');
        }]);

        return response()->json([
            'success' => true,
            'data'    => $collection,
        ]);
    }

    /**
     * Update collection details.
     */
    public function update(StoreCollectionRequest $request, Collection $collection): JsonResponse
    {
        if ($collection->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $collection->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Colección actualizada.',
            'data'    => $collection,
        ]);
    }

    /**
     * Delete a collection.
     */
    public function destroy(Request $request, Collection $collection): JsonResponse
    {
        if ($collection->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $collection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Colección eliminada.',
        ]);
    }

    /**
     * Add a game to a collection.
     */
    public function addGame(Request $request, Collection $collection, Game $game): JsonResponse
    {
        if ($collection->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($collection->games()->where('game_id', $game->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El juego ya está en la colección.',
            ], 400); // 400 Bad Request
        }

        $collection->games()->attach($game->id);

        return response()->json([
            'success' => true,
            'message' => 'Juego añadido a la colección.',
        ]);
    }

    /**
     * Remove a game from a collection.
     */
    public function removeGame(Request $request, Collection $collection, Game $game): JsonResponse
    {
        if ($collection->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $collection->games()->detach($game->id);

        return response()->json([
            'success' => true,
            'message' => 'Juego removido de la colección.',
        ]);
    }
}
