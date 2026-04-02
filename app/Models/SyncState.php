<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncState extends Model
{
    protected $fillable = [
        'source',
        'last_page',
        'total_pages',
        'total_items',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get or create a sync state for the given source.
     */
    public static function forSource(string $source): static
    {
        return static::firstOrCreate(['source' => $source], [
            'last_page' => 0,
        ]);
    }

    /**
     * Advance to the next page and record sync time.
     */
    public function advance(int $totalItems = null, int $totalPages = null): void
    {
        $this->increment('last_page');
        $this->last_synced_at = now();
        if ($totalItems) $this->total_items = $totalItems;
        if ($totalPages) $this->total_pages = $totalPages;
        $this->save();
    }

    /**
     * Reset to start from page 1 again.
     */
    public function reset(): void
    {
        $this->update(['last_page' => 0, 'last_synced_at' => null]);
    }
}
