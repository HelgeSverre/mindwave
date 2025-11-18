<?php

use Mindwave\Mindwave\Tools\DuckDuckGoSearch;

it('can search for a term on DuckDuckGo', function () {
    $tool = new DuckDuckGoSearch;

    $response = $tool->run('helge sverre');

    expect($response)->toBeString()->not()->toBeEmpty();
})->skip('DuckDuckGo API may not return results for all queries');
