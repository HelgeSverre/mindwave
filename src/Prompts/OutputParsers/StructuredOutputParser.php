<?php

namespace Mindwave\Mindwave\Prompts\OutputParsers;

use Illuminate\Support\Collection;
use Mindwave\Mindwave\Contracts\OutputParser;
use ReflectionClass;

class StructuredOutputParser implements OutputParser
{
    protected $schema;

    public function __construct($schema = null)
    {
        $this->schema = $schema;
    }

    public function fromClass($schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    public function getSchemaStructure(): array
    {
        $reflectionClass = new ReflectionClass($this->schema);
        $properties = [];
        $required = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();
            $propertyType = $property->getType()->getName();

            if ($property->getType()->allowsNull() === false) {
                $required[] = $propertyName;
            }

            $properties[$propertyName] = [
                'role' => match ($propertyType) {
                    'string', 'int', 'float', 'bool' => $propertyType,
                    'array', Collection::class => 'array',
                    default => 'object',
                },
            ];
        }

        return [
            'properties' => $properties,
            'required' => $required,
        ];
    }

    public function getFormatInstructions(): string
    {
        $schema = json_encode($this->getSchemaStructure());

        return trim('
RESPONSE FORMAT INSTRUCTIONS
----------------------------
The output should be formatted as a JSON instance that conforms to the JSON schema below.

As an example, for the schema {{"properties": {{"foo": {{"title": "Foo", "description": "a list of strings", "role": "array", "items": {{"role": "string"}}}}}}, "required": ["foo"]}}}}
the object {{"foo": ["bar", "baz"]}} is a well-formatted instance of the schema. The object {{"properties": {{"foo": ["bar", "baz"]}}}} is not well-formatted.

Here is the output schema:
```json
'.$schema.'
```
Remember to respond with a JSON blob, and NOTHING else.');
    }

    public function parse(string $text): mixed
    {
        $reflectionClass = new ReflectionClass($this->schema);
        $data = json_decode($text, true);

        if (! $data) {
            // TODO(29 May 2023) ~ Helge: Throw custom exception
            return null;
        }

        $instance = new $this->schema();

        foreach ($data as $key => $value) {

            $type = $reflectionClass->getProperty($key)->getType();

            // TODO(29 May 2023) ~ Helge: There are probably libraries that do this in a more clever way, but this is fine for now.
            $instance->{$key} = match ($type->getName()) {
                'bool' => boolval($value),
                'int' => intval($value),
                'float' => floatval($value),
                Collection::class => collect($value),
                default => $value,
            };
        }

        return $instance;
    }
}
