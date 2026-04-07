<?php

namespace App\Console\Commands;

use App\Models\SyncState;
use App\Services\Api\RawgService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncRawgGames extends Command
{
    protected $signature = 'rawg:sync
                            {--pages=40 : How many pages to fetch in this run}
                            {--per-page=40 : Results per page (max 40)}
                            {--reset : Reset progress and start from page 1}';

    protected $description = 'Sync games from RAWG API into the local database, resuming from the last saved page.';

    public function __construct(protected RawgService $rawg)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $state   = SyncState::forSource('rawg_games');
        $pages   = (int) $this->option('pages');
        $perPage = min((int) $this->option('per-page'), 40);

        if ($this->option('reset')) {
            $state->reset();
            $this->info('Sync state reset. Starting from page 1.');
        }

        $nextPage = $state->last_page + 1;

        $this->info("▶ Starting RAWG sync: pages {$nextPage} to " . ($nextPage + $pages - 1) . " ({$perPage} games/page)");

        for ($i = 0; $i < $pages; $i++) {
            $page = $nextPage + $i;

            $this->line("  Fetching page {$page}...");

            $data = $this->rawg->fetchGames($page, $perPage);

            if (!$data) {
                $this->error("  ✗ Failed to fetch page {$page} from RAWG.");
                Log::error("rawg:sync failed on page {$page}");
                break;
            }

            $count      = count($data['results'] ?? []);
            $totalItems = $data['count'] ?? null;
            $totalPages = $totalItems ? (int) ceil($totalItems / $perPage) : null;

            $synced  = 0;
            $skipped = 0;

            foreach ($data['results'] as $rawgGame) {
                try {
                    $this->rawg->upsertGameFromList($rawgGame);
                    $synced++;
                } catch (\Exception $e) {
                    $skipped++;
                    Log::warning('rawg:sync upsert error', [
                        'game'  => $rawgGame['name'] ?? '?',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $state->advance($totalItems, $totalPages);

            $this->line("  ✓ Page {$page}: {$synced} synced, {$skipped} skipped. " .
                "Total RAWG: {$totalItems} games (~{$totalPages} pages).");

            // Stop if RAWG says there's no more data
            if (empty($data['next'])) {
                $this->info('  ✓ No more pages in RAWG. Sync complete.');
                break;
            }
        }

        $this->info("✅ Done. Last saved page: {$state->fresh()->last_page}");

        return Command::SUCCESS;
    }
}
