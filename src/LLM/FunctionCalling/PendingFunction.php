<?php

namespace Mindwave\Mindwave\LLM\FunctionCalling;

use Illuminate\Support\Str;
use Mindwave\Mindwave\LLM\FunctionCalling\Attributes\Description;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

class PendingFunction
{
    protected string $name;

    protected string $description = '';

    protected array $parameters = [];

    protected array $required = [];

    public function __construct(string $name, ?string $description = null)
    {
        $this->name = $name;
        $this->description = $description ?: '';
    }

    public function setDescription(string $string)
    {
        $this->description = $string;

        return $this;
    }

    public static function makeFromClosure($name, $closure, $description = null)
    {
        $instance = new self($name, $description);
        $instance->fromClosure($closure);

        return $instance;
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

    /**
     * @throws ReflectionException
     */
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

            // Override the description if the parameter has a Description attribute
            if ($override = $parameter->getAttributes(Description::class)) {
                $description = $override[0]->newInstance()->description;
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

    public static function getTypeFromParameter(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        if (! $type) {
            return 'mixed'; // Return 'mixed' if the type is not available
        }

        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;

        return match ($typeName) {
            'int' => 'integer',
            'float' => 'number',
            default => $typeName
        };

    }

    public function build(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->parameters,
                    'required' => $this->required,
                ],
            ],
        ];
    }

}
