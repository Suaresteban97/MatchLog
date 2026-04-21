<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function __construct(protected ContributionService $contributionService) {}

    /**
     * List pending contributions for the moderation panel.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $contributions = $this->contributionService->getPendingContributions($perPage);

        return response()->json([
            'success' => true,
            'data'    => $contributions,
        ]);
    }

    /**
     * Resolve a contribution (approve or reject).
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status'         => ['required', 'string', 'in:approved,rejected'],
            'reviewer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $contribution = $this->contributionService->resolve(
                $id,
                $validated['status'],
                $validated['reviewer_notes'] ?? null,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Contribución ' . ($validated['status'] === 'approved' ? 'aprobada y aplicada con éxito.' : 'rechazada.'),
                'data'    => $contribution,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
