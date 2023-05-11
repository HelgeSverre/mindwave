<?php

namespace Mindwave\Mindwave\Commands;

use Illuminate\Console\Command;

class MindwaveCommand extends Command
{
    public $signature = 'mindwave';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
