<?php

namespace Mindwave\Mindwave\Commands;

use Illuminate\Console\Command;
use Mindwave\Mindwave\Context\TntSearch\EphemeralIndexManager;

class IndexStatsCommand extends Command
{
    protected $signature = 'mindwave:index-stats';

    protected $description = 'Display statistics about TNTSearch context indexes';

    public function handle(): int
    {
        $manager = app(EphemeralIndexManager::class);
        $stats = $manager->getStats();

        $this->info('📊 TNTSearch Index Statistics');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Indexes', $stats['count']],
                ['Total Size (MB)', $stats['total_size_mb']],
                ['Total Size (Bytes)', number_format($stats['total_size_bytes'])],
                ['Storage Path', $stats['storage_path']],
            ]
        );

        if ($stats['count'] > 0) {
            $this->newLine();
            $this->info('💡 Tip: Run "php artisan mindwave:clear-indexes" to remove old indexes');
        }

        return self::SUCCESS;
    }
}
