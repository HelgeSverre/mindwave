<?php

namespace Mindwave\Mindwave\LLM\Functions;

use Illuminate\Support\Str;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

class PendingFunction
{
    protected string $name;

    protected string $description = '';

    protected array $parameters = [];

    protected array $required = [];

    public function __construct(string $name, string $description = null)
    {
        $this->name = $name;
        $this->description = $description ?: '';
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function addParameter(
        string $name,
        string $type,
        string $description,
        bool $isRequired = false,
        array $enum = []
    ): self {
        $this->parameters[$name] = [
            'type' => $type,
            'description' => $description,
        ];
        if ($enum) {
            $this->parameters[$name]['enum'] = $enum;
        }
        if ($isRequired) {
            $this->required[] = $name;
        }

        return $this;
    }

    public function fromClosure(callable $func): self
    {
        $reflection = new ReflectionFunction($func);

        // Get the doc comment of the function
        $docComment = $reflection->getDocComment();

        foreach ($reflection->getParameters() as $parameter) {
            $parameterName = $parameter->getName();

            // Initialize description as a default value
            $description = '';

            // Use a regular expression to extract the parameter description from the doc comment
            if ($docComment !== false) {
                $description = Str::of($docComment)->after("\$$parameterName")->before("\n")->trim()->toString();
            }

            $this->parameters[$parameterName] = [
                'type' => $this->getTypeFromParameter($parameter),
                'description' => $description,
            ];

            if (! $parameter->isDefaultValueAvailable()) {
                $this->required[] = $parameterName;
            }
        }

        return $this;
    }

    private function getTypeFromParameter(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();
        if (! $type) {
            return 'mixed'; // Return 'mixed' if the type is not available
        }

        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;

        if ($type->allowsNull()) {
            return 'null|'.$typeName;
        }

        return $typeName;
    }

    public function build(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => [
                'type' => 'object',
                'properties' => $this->parameters,
                'required' => $this->required,
            ],
        ];
    }
}
