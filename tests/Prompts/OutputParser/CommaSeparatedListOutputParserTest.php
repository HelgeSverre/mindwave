<?php

use Mindwave\Mindwave\Prompts\OutputParsers\CommaSeparatedListOutputParser;

it('can parse comma separated output', function () {

    $parser = new CommaSeparatedListOutputParser();

    expect($parser->parse('monsters, bananas, flies, sausages'))->toEqual([
        'monsters',
        'bananas',
        'flies',
        'sausages',
    ]);
});
