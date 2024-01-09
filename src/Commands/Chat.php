<?php

namespace Mindwave\Mindwave\Commands;

use Illuminate\Console\Command;
use Mindwave\Mindwave\Facades\LLM;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;
use function Laravel\Prompts\info;

class MindwaveCommand extends Command
{
    public $signature = 'mindwave:chat {--llm=default}';

    public function handle(): int
    {

        $llm = LLM::driver($this->option('llm'));

        intro('Welcome to Mindwave Chat!');
        info('Using: ' . $llm->);



        $prompt = text('> ');

        return self::SUCCESS;
    }
}
