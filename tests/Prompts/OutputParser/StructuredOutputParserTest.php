<?php

use Illuminate\Support\Collection;
use Mindwave\Mindwave\Exceptions\MindwaveParseException;
use Mindwave\Mindwave\Prompts\OutputParsers\StructuredOutputParser;

class Person
{
    public string $name;

    public ?int $age;

    public ?bool $hasBusiness;

    public ?array $interests;

    public ?Collection $tags;
}

it('can convert a class into a schema for StructuredOutputParser', function () {
    $parser = new StructuredOutputParser(Person::class);

    expect($parser->getSchemaStructure())
        ->toBe([
            'properties' => [
                'name' => ['role' => 'string'],
                'age' => ['role' => 'int'],
                'hasBusiness' => ['role' => 'bool'],
                'interests' => ['role' => 'array'],
                'tags' => ['role' => 'array'],
            ],
            'required' => ['name'],
        ]);
});

it('can parse response into class instance', function () {
    $parser = new StructuredOutputParser(Person::class);

    /** @var Person $person */
    $person = $parser->parse('{"name": "Lila Jones", "age": 28, "hasBusiness": true, "interests": ["hiking", "reading", "painting"], "tags": ["adventurous", "creative", "entrepreneur"]}');

    expect($person)->toBeInstanceOf(Person::class);
    expect($person->name)->toBe('Lila Jones');
    expect($person->age)->toBe(28);
    expect($person->hasBusiness)->toBe(true);
    expect($person->interests)->toBe(['hiking', 'reading', 'painting']);
    expect($person->tags)->toEqual(collect(['adventurous', 'creative', 'entrepreneur']));
});

it('throws MindwaveParseException if parsing data fails', function () {
    $parser = new StructuredOutputParser(Person::class);

    expect(fn () => $parser->parse('broken and invalid data'))
        ->toThrow(MindwaveParseException::class);
});

it('includes raw text in parse exception', function () {
    $parser = new StructuredOutputParser(Person::class);
    $invalidText = 'broken and invalid data';

    try {
        $parser->parse($invalidText);
    } catch (MindwaveParseException $e) {
        expect($e->getRawText())->toBe($invalidText);
        expect($e->getMessage())->toContain('Failed to parse LLM output as JSON');
    }
});
