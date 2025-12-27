<?php

namespace Mindwave\Mindwave\Tools;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Contracts\Tool;
use RuntimeException;

class BraveSearch implements Tool
{
    public function __construct(
        protected ?string $apiKey = null,
        protected int $resultsCount = 5
    ) {
        $this->apiKey ??= config('mindwave-tools.brave_search.api_key');
        $this->resultsCount = config('mindwave-tools.brave_search.results_count', $this->resultsCount);
    }

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
        if (empty($this->apiKey)) {
            throw new RuntimeException('Brave Search API key not configured. Set BRAVE_SEARCH_API_KEY in your environment.');
        }

        try {
            $response = Http::withHeader('X-Subscription-Token', $this->apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->get('https://api.search.brave.com/res/v1/web/search', [
                    'q' => $input,
                    'count' => $this->resultsCount,
                ]);

            $response->throw();

            $results = $response->collect('web.results');

            if ($results->isEmpty()) {
                return json_encode(['message' => 'No results found for the query.']);
            }

            return $results
                ->map(fn ($result) => [
                    'title' => $result['title'] ?? '',
                    'url' => $result['url'] ?? '',
                    'description' => strip_tags($result['description'] ?? ''),
                ])
                ->toJson();
        } catch (RequestException $e) {
            return json_encode([
                'error' => 'Search request failed',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
