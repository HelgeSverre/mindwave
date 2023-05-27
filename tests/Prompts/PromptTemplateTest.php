<?php

use Mindwave\Mindwave\Contracts\OutputParser;
use Mindwave\Mindwave\Prompts\OutputParsers\CommaSeparatedListOutputParser;
use Mindwave\Mindwave\Prompts\OutputParsers\JsonListOutputParser;
use Mindwave\Mindwave\Prompts\OutputParsers\JsonOutputParser;
use Mindwave\Mindwave\Prompts\PromptTemplate;

it('can create a new prompt template', function () {
    $template = 'This is a {variable} template.';

    $promptTemplate = PromptTemplate::create($template);

    expect($promptTemplate)->toBeInstanceOf(PromptTemplate::class);
});

it('can format a template with input variables', function () {
    $template = 'This is a {variable} template.';

    $prompt = PromptTemplate::create($template)->format(['variable' => 'test']);

    expect($prompt)
        ->toBe('This is a test template.');
});

it('can append an output parser to a template', function () {

    $prompt = PromptTemplate::create('This is a test template.', new class implements OutputParser
    {
        public function getFormatInstructions(): string
        {
            return 'TESTING';
        }

        public function parse(string $text): mixed
        {
            return $text;
        }
    });

    expect($prompt->format())->toEndWith('TESTING');
});

it('can parse comma separated output', function () {

    $parser = new CommaSeparatedListOutputParser();

    expect($parser->parse('monsters, bananas, flies, sausages'))->toEqual([
        'monsters',
        'bananas',
        'flies',
        'sausages',
    ]);
});

it('can parse json array as array', function () {

    $parser = new JsonListOutputParser();

    expect($parser->parse('```json{"data": ["monsters", "bananas", "flies", "sausages"]}```'))->toEqual([
        'monsters',
        'bananas',
        'flies',
        'sausages',
    ]);
});

it('can parse a response', function () {
    $prompt = PromptTemplate::create('Test prompt', new JsonOutputParser())
        ->parse('```json { "hello": "world", "nice":["mindwave", "package"] } ```');

    expect($prompt)
        ->toBeArray()
        ->and($prompt)
        ->toHaveKey('hello', 'world')
        ->toHaveKey('nice', ['mindwave', 'package']);
});

it('can convert a template to a string', function () {
    $prompt = PromptTemplate::create('This is a {variable} template. ')
        ->format(['variable' => 'test']);

    expect($prompt)->toBe('This is a test template.');
});

it('json list output parser generates a list from constructor', function () {
    $outputParser = new JsonListOutputParser();
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
    $outputParser = new JsonListOutputParser();

    $prompt = PromptTemplate::create(
        template: 'Generate 10 keywords for {topic}',
    )->withOutputParser($outputParser)->format([
        'topic' => 'Laravel',
    ]);

    expect($prompt)->toContain('Generate 10 keywords for Laravel');
    expect($prompt)->toContain($outputParser->getFormatInstructions());
});

it('formats the template with input variables', function () {
    expect(
        PromptTemplate::create('Hello, {name}! Your {product} is ready.')
            ->format([
                'name' => 'John',
                'product' => 'Mindwave',
            ])
    )->toBe('Hello, John! Your Mindwave is ready.');
});

it('overrides the variable formatter', function () {

    $formattedString = PromptTemplate::create('Hello, [name]! Your [product] is ready.')
        ->withPlaceholderFormatter(fn ($variable) => '['.$variable.']')
        ->format([
            'name' => 'John',
            'product' => 'Mindwave',
        ]);

    expect($formattedString)->toBe('Hello, John! Your Mindwave is ready.');
});
