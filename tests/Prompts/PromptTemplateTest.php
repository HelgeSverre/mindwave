<?php

use Mindwave\Mindwave\Contracts\OutputParser;
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

it('can convert a template to a string', function () {
    $prompt = PromptTemplate::create('This is a {variable} template. ')
        ->format(['variable' => 'test']);

    expect($prompt)->toBe('This is a test template.');
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
