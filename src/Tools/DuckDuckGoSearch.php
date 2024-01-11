<?php

namespace Mindwave\Mindwave\Tools;

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Contracts\Tool;

class DuckDuckGoSearch implements Tool
{
    public function name(): string
    {
        return 'DuckDuckGo Search';
    }

    public function description(): string
    {

        return 'Searches the web for information';
    }

    public function run($query): string
    {

        // use http laravel client
        $response = Http::get('https://api.duckduckgo.com/', [
            'q' => $query,
            'format' => 'json',
        ]);

        return $response->json('AbstractText') ?? $response->json('RelatedTopics.0.Text');

    }
}
