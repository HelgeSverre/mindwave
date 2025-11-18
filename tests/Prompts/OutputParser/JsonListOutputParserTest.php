<?php

use Mindwave\Mindwave\Prompts\OutputParsers\JsonListOutputParser;
use Mindwave\Mindwave\Prompts\PromptTemplate;

it('json list output parser generates a list from constructor', function () {
    $outputParser = new JsonListOutputParser;
    $prompt = PromptTemplate::create(
        template: 'Generate 10 keywords for {topic}',
        outputParser: $outputParser
    )->format([
        'topic' => 'Mindwave',
    ]);

    expect($prompt)->toContain('Generate 10 keywords for Mindwave');
    expect($prompt)->toContain($outputParser->getFormatInstructions());
});

it('json list output parser generates a list from method', function () {
    $outputParser = new JsonListOutputParser;

    $prompt = PromptTemplate::create(
        template: 'Generate 10 keywords for {topic}',
    )->withOutputParser($outputParser)->format([
        'topic' => 'Laravel',
    ]);

    expect($prompt)->toContain('Generate 10 keywords for Laravel');
    expect($prompt)->toContain($outputParser->getFormatInstructions());
});

it('can parse json array as array', function () {

    $parser = new JsonListOutputParser;

    expect($parser->parse('```json{"data": ["monsters", "bananas", "flies", "sausages"]}```'))->toEqual([
        'monsters',
        'bananas',
        'flies',
        'sausages',
    ]);
});
