<?php

use Mindwave\Mindwave\Tools\BraveSearch;

describe('BraveSearch', function () {
    it('returns tool name and description', function () {
        $tool = new BraveSearch('fake-key');

        expect($tool->name())->toBe('Brave Web Search');
        expect($tool->description())->toContain('Brave Search API');
    });

    it('throws exception when API key is not configured', function () {
        // Ensure no config is set
        config(['mindwave-tools.brave_search.api_key' => null]);

        $tool = new BraveSearch;

        expect(fn () => $tool->run('test query'))
            ->toThrow(RuntimeException::class, 'Brave Search API key not configured');
    });

    it('can search with real API key')
        ->skip('Requires BRAVE_SEARCH_API_KEY environment variable');
});
