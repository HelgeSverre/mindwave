<?php

namespace Mindwave\Mindwave\Commands;

use Illuminate\Console\Command;
use Mindwave\Mindwave\Facades\LLM;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\text;

class MindwaveCommand extends Command
{
    public $signature = 'mindwave:chat {--llm=default}';

    public function handle(): int
    {

        /** @var LLM $llm */
        $llm = LLM::driver($this->option('llm'));

        intro('Welcome to Mindwave Chat!');
        info('Using: '.$this->option('llm'));

        $prompt = text('> ');

        return self::SUCCESS;
    }
}
