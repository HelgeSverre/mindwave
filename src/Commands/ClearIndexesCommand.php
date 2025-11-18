<?php

namespace Mindwave\Mindwave\Commands;

use Illuminate\Console\Command;
use Mindwave\Mindwave\Context\TntSearch\EphemeralIndexManager;

class ClearIndexesCommand extends Command
{
    protected $signature = 'mindwave:clear-indexes
                           {--ttl=24 : Time to live in hours (default: 24)}
                           {--force : Skip confirmation}';

    protected $description = 'Clear old TNTSearch context indexes';

    public function handle(): int
    {
        $manager = app(EphemeralIndexManager::class);
        $ttlHours = (int) $this->option('ttl');

        // Get current stats
        $statsBefore = $manager->getStats();

        if ($statsBefore['count'] === 0) {
            $this->info('✨ No indexes to clear');

            return self::SUCCESS;
        }

        // Show what will be cleared
        $this->info("🔍 Found {$statsBefore['count']} index(es) ({$statsBefore['total_size_mb']} MB)");
        $this->info("⏰ Clearing indexes older than {$ttlHours} hours");

        // Confirm unless --force
        if (! $this->option('force') && ! $this->confirm('Do you want to proceed?', true)) {
            $this->comment('Cancelled');

            return self::SUCCESS;
        }

        // Clear indexes
        $deleted = $manager->cleanup($ttlHours);

        // Show results
        $statsAfter = $manager->getStats();
        $freedMb = $statsBefore['total_size_mb'] - $statsAfter['total_size_mb'];

        $this->newLine();
        $this->info("✅ Cleared {$deleted} index(es)");
        $this->info("💾 Freed {$freedMb} MB");

        if ($statsAfter['count'] > 0) {
            $this->comment("ℹ️  {$statsAfter['count']} active index(es) remaining");
        }

        return self::SUCCESS;
    }
}
