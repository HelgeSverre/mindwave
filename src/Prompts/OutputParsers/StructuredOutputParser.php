<?php

namespace Mindwave\Mindwave\Prompts\OutputParsers;

use Illuminate\Support\Collection;
use Mindwave\Mindwave\Contracts\OutputParser;
use Mindwave\Mindwave\Exceptions\MindwaveParseException;
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

    /**
     * Parse the LLM output text into a structured object.
     *
     * @throws MindwaveParseException When the text cannot be parsed as valid JSON
     */
    public function parse(string $text): mixed
    {
        $reflectionClass = new ReflectionClass($this->schema);
        $data = json_decode($text, true);

        if (! $data) {
            throw MindwaveParseException::invalidJson($text, json_last_error_msg());
        }

        $instance = new $this->schema;

        foreach ($data as $key => $value) {
            if (! $reflectionClass->hasProperty($key)) {
                continue;
            }

            $type = $reflectionClass->getProperty($key)->getType();

            $instance->{$key} = match ($type?->getName()) {
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
