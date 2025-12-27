<?php

namespace Mindwave\Mindwave\Tools;

use Illuminate\Http\Client\RequestException;
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
        return 'Searches the web for information using DuckDuckGo Instant Answer API.';
    }

    public function run($query): string
    {
        try {
            $response = Http::timeout(30)
                ->get('https://api.duckduckgo.com/', [
                    'q' => $query,
                    'format' => 'json',
                ]);

            $response->throw();

            $abstractText = $response->json('AbstractText');
            if (! empty($abstractText)) {
                return $abstractText;
            }

            $relatedTopic = $response->json('RelatedTopics.0.Text');
            if (! empty($relatedTopic)) {
                return $relatedTopic;
            }

            return 'No results found for the query.';
        } catch (RequestException $e) {
            return 'Search failed: '.$e->getMessage();
        }
    }
}
