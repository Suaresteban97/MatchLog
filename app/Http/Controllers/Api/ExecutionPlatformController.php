<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExecutionPlatform;
use Illuminate\Http\Request;
use App\Http\Requests\ExecutionPlatform\AttachExecutionPlatformRequest;

class ExecutionPlatformController extends Controller
{
    /**
     * Get all available execution platforms.
     */
    public function index()
    {
        $platforms = ExecutionPlatform::all();
        return response()->json(['platforms' => $platforms], 200);
    }

    /**
     * Get authenticated user's execution platforms.
     */
    public function userPlatforms(Request $request)
    {
        $platforms = $request->user()->executionPlatforms()
            ->withPivot('account_identifier', 'created_at')
            ->get();

        return response()->json(['platforms' => $platforms], 200);
    }

    /**
     * Link an execution platform to the authenticated user.
     */
    public function attach(AttachExecutionPlatformRequest $request)
    {
        $validated = $request->validated();

        // Check if already linked
        if ($request->user()->executionPlatforms()->where('execution_platforms.id', $validated['execution_platform_id'])->exists()) {
            return response()->json(['message' => 'Esta plataforma ya está vinculada'], 200);
        }

        $request->user()->executionPlatforms()->attach($validated['execution_platform_id'], [
            'account_identifier' => $validated['account_identifier'] ?? null
        ]);

        return response()->json(['message' => 'Plataforma vinculada correctamente'], 201);
    }

    /**
     * Unlink an execution platform from the authenticated user.
     */
    public function detach(AttachExecutionPlatformRequest $request)
    {
        $platformId = $request->validated()['execution_platform_id'];

        $request->user()->executionPlatforms()->detach($platformId);

        return response()->json(['message' => 'Plataforma desvinculada correctamente'], 200);
    }
}
