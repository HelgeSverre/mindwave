<?php

namespace Mindwave\Mindwave\Tools;

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Contracts\Tool;

class BraveSearch implements Tool
{
    public function name(): string
    {
        return 'Norwegian Phonebook Search';
    }

    public function description(): string
    {
        return 'Searches the Norwegian Phonebook for phone numbers and names of people residing in norway, search by name or phone number';
    }

    public function run($input): string
    {
        // TODO: cleanup later
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
