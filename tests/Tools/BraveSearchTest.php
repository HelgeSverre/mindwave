<?php

use Mindwave\Mindwave\Tools\BraveSearch;

it('Cna find my number', function () {
    $tool = new BraveSearch();

    $response = $tool->run('helge sverre liseth');

    dd($response);

});
