<?php

namespace App\Http\Controllers\Api;

use App\Models\Contribution;
use App\Services\ContributionService;
use App\Http\Requests\Contributions\StoreContributionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContributionController extends Controller
{
    public function __construct(protected ContributionService $contributionService) {}

    /**
     * Submit a new community contribution.
     *
     * POST /api/contributions
     */
    public function store(StoreContributionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $contribution = $this->contributionService->submit(
                user:          $request->user(),
                type:          $validated['contributable_type'],
                resourceId:    (int) $validated['contributable_id'],
                field:         $validated['field'],
                proposedValue: $validated['proposed_value'],
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tu contribución fue enviada y está pendiente de revisión.',
            'data'    => $contribution,
        ], 201);
    }

    /**
     * List contributions submitted by the authenticated user.
     *
     * GET /api/contributions
     */
    public function index(Request $request): JsonResponse
    {
        $perPage       = (int) $request->input('per_page', 15);
        $contributions = $this->contributionService->listByUser($request->user(), $perPage);

        return response()->json($contributions);
    }

    /**
     * List contributions targeting a specific resource.
     *
     * GET /api/contributions/resource/{type}/{id}?status=pending&per_page=15
     *
     * @param string $type  'game' | 'genre' | 'platform'
     * @param int    $id
     */
    public function forResource(Request $request, string $type, int $id): JsonResponse
    {
        // Validate type early so we return a clear error instead of an exception
        if (!in_array($type, ['game', 'genre', 'platform'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid resource type. Use: game, genre, platform.',
            ], 422);
        }

        $status  = $request->input('status', 'pending');
        $perPage = (int) $request->input('per_page', 15);

        try {
            $contributions = $this->contributionService->listForResource($type, $id, $status, $perPage);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
        }

        return response()->json($contributions);
    }
}
