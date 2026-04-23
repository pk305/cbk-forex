<?php

namespace Pk305\CbkForex\Commands;

use Illuminate\Console\Command;
use Pk305\CbkForex\Services\CbkForexService;

class FetchCbkForexCommand extends Command
{
    protected $signature = 'cbk-forex:fetch
                            {--prune : Also prune old records after fetching}
                            {--quiet-log : Suppress table output, rely on logs only}';

    protected $description = 'Fetch the latest CBK foreign exchange rates and store them in the database.';

    public function handle(CbkForexService $service): int
    {
        $this->info('Fetching CBK foreign exchange rates...');

        try {
            $result = $service->fetchAndStore();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (!$this->option('quiet-log')) {
            $this->newLine();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Date',    $result['date']],
                    ['Fetched', $result['fetched']],
                    ['Saved',   $result['saved']],
                    ['Skipped', $result['skipped']],
                ]
            );
            $this->newLine();
        }

        $this->info("Done. {$result['saved']} rate(s) saved for {$result['date']}.");

        // Optional pruning
        if ($this->option('prune') || config('cbk-forex.prune.enabled', false)) {
            $deleted = $service->pruneOldRecords();
            $this->info("Pruned {$deleted} old record(s).");
        }

        return self::SUCCESS;
    }
}
