<?php

use Mindwave\Mindwave\Prompts\OutputParsers\JsonOutputParser;
use Mindwave\Mindwave\Prompts\PromptTemplate;

it('can parse a response', function () {
    $prompt = PromptTemplate::create('Test prompt', new JsonOutputParser)
        ->parse('```json { "hello": "world", "nice":["mindwave", "package"] } ```');

    expect($prompt)
        ->toBeArray()
        ->and($prompt)
        ->toHaveKey('hello', 'world')
        ->toHaveKey('nice', ['mindwave', 'package']);
});
