<?php

use Mindwave\Mindwave\Tools\PhonebookSearch;

it('Cna find my number', function () {
    $tool = new PhonebookSearch;

    $response = $tool->run('helge sverre liseth');

    dd($response);

});
