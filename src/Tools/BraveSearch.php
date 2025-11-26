<?php

namespace Mindwave\Mindwave\Tools;

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Contracts\Tool;

class BraveSearch implements Tool
{
    public function name(): string
    {
        return 'Brave Web Search';
    }

    public function description(): string
    {
        return 'Search the web using Brave Search API. Returns titles, URLs, and descriptions of relevant web pages.';
    }

    public function run($input): string
    {
        return Http::withHeader('X-Subscription-Token', env('BRAVE_SEARCH_API_KEY'))
            ->acceptJson()
            ->asJson()
            ->get('https://api.search.brave.com/res/v1/web/search', [
                'q' => $input,
                'count' => 5,
            ])
            ->collect('web.results')
            ->map(fn ($result) => [
                'title' => $result['title'],
                'url' => $result['url'],
                'description' => strip_tags($result['description']),
            ])
            ->toJson();

    }
}
