<?php

use Mindwave\Mindwave\Tools\DuckDuckGoSearch;

it('can search for a term on DuckDuckGo', function () {
    $tool = new DuckDuckGoSearch;

    $response = $tool->run('helge sverre');

    dd($response);

});
