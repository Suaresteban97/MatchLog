<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Contribution extends Model
{
    protected $fillable = [
        'user_id',
        'contributable_type',
        'contributable_id',
        'field',
        'current_value',
        'proposed_value',
        'status',
        'reviewer_id',
        'rejection_reason',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * The polymorphic target (Game, Genre or GamePlatform).
     */
    public function contributable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who submitted this contribution.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The moderator who reviewed this contribution (nullable).
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Map a short resource type string to its full model class name.
     * Used by the ContributionService to resolve polymorphic types safely
     * without exposing arbitrary class names to the HTTP layer.
     *
     * @throws \InvalidArgumentException
     */
    public static function resolveContributableClass(string $type): string
    {
        return match ($type) {
            'game'     => Game::class,
            'genre'    => Genre::class,
            'platform' => GamePlatform::class,
            default    => throw new \InvalidArgumentException("Invalid contributable type: {$type}"),
        };
    }
}
