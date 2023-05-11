<?php

namespace App\Robot\Tools;

use Exception;

class GoogleSearch implements Tool
{
    public function name(): string
    {
        return 'Google Search';
    }

    public function description(): string
    {
        return 'A wrapper around Google Search. Useful for when you need to answer questions about current events. Input should be a search query.';
    }

    public function run($input): string
    {
        // TODO(11 May 2023) ~ Helge: implement
        throw new Exception('Not implemented');
    }
}
