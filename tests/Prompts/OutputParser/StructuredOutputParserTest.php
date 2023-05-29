<?php

use Illuminate\Support\Collection;
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
                'name' => ['type' => 'string'],
                'age' => ['type' => 'int'],
                'hasBusiness' => ['type' => 'bool'],
                'interests' => ['type' => 'array'],
                'tags' => ['type' => 'array'],
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

it('can returns null if parsing data fails.', function () {
    $parser = new StructuredOutputParser(Person::class);

    $person = $parser->parse('broken and invalid data');

    expect($person)->toBeNull(Person::class);
});
