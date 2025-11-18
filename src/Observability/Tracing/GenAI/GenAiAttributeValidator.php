<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing\GenAI;

use InvalidArgumentException;

/**
 * GenAI Attribute Validator
 *
 * Validates GenAI semantic convention attributes to ensure they conform
 * to OpenTelemetry specifications and data type requirements.
 */
final class GenAiAttributeValidator
{
    /**
     * Validate a single attribute
     *
     * @param string $name The attribute name
     * @param mixed $value The attribute value
     * @return bool
     * @throws InvalidArgumentException If validation fails
     */
    public static function validate(string $name, mixed $value): bool
    {
        if ($value === null) {
            return true; // Null values are allowed (optional attributes)
        }

        return match ($name) {
            // String attributes
            GenAiAttributes::GEN_AI_OPERATION_NAME => self::validateOperation($value),
            GenAiAttributes::GEN_AI_PROVIDER_NAME => self::validateProvider($value),
            GenAiAttributes::GEN_AI_REQUEST_MODEL,
            GenAiAttributes::GEN_AI_RESPONSE_MODEL,
            GenAiAttributes::GEN_AI_RESPONSE_ID,
            GenAiAttributes::GEN_AI_TOOL_CALL_NAME,
            GenAiAttributes::SERVER_ADDRESS => self::validateString($name, $value),

            // Numeric attributes - floats
            GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE,
            GenAiAttributes::GEN_AI_REQUEST_TOP_P => self::validateFloat($name, $value, 0.0, 2.0),
            GenAiAttributes::GEN_AI_REQUEST_FREQUENCY_PENALTY,
            GenAiAttributes::GEN_AI_REQUEST_PRESENCE_PENALTY => self::validateFloat($name, $value, -2.0, 2.0),

            // Numeric attributes - integers
            GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS,
            GenAiAttributes::GEN_AI_REQUEST_TOP_K,
            GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS,
            GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS,
            GenAiAttributes::GEN_AI_USAGE_TOTAL_TOKENS,
            GenAiAttributes::GEN_AI_USAGE_CACHE_READ_TOKENS,
            GenAiAttributes::GEN_AI_USAGE_CACHE_CREATION_TOKENS,
            GenAiAttributes::GEN_AI_EMBEDDINGS_DIMENSION,
            GenAiAttributes::SERVER_PORT => self::validateInteger($name, $value, 0),

            // Array attributes
            GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS,
            GenAiAttributes::GEN_AI_REQUEST_STOP_SEQUENCES => self::validateArray($name, $value),

            // Array of objects (messages)
            GenAiAttributes::GEN_AI_INPUT_MESSAGES,
            GenAiAttributes::GEN_AI_OUTPUT_MESSAGES => self::validateMessages($name, $value),

            // String or array (system instructions, tool args/results)
            GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS,
            GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS,
            GenAiAttributes::GEN_AI_TOOL_CALL_RESULT,
            GenAiAttributes::GEN_AI_EMBEDDINGS_INPUT => self::validateStringOrArray($name, $value),

            default => true, // Unknown attributes are allowed
        };
    }

    /**
     * Validate multiple attributes
     *
     * @param array<string, mixed> $attributes
     * @return bool
     * @throws InvalidArgumentException If any validation fails
     */
    public static function validateBatch(array $attributes): bool
    {
        foreach ($attributes as $name => $value) {
            self::validate($name, $value);
        }

        return true;
    }

    /**
     * Validate required attributes are present
     *
     * @param array<string, mixed> $attributes
     * @return bool
     * @throws InvalidArgumentException If required attributes are missing
     */
    public static function validateRequired(array $attributes): bool
    {
        foreach (GenAiAttributes::getRequiredAttributes() as $required) {
            if (!isset($attributes[$required])) {
                throw new InvalidArgumentException(
                    "Required attribute '{$required}' is missing"
                );
            }

            if ($attributes[$required] === null || $attributes[$required] === '') {
                throw new InvalidArgumentException(
                    "Required attribute '{$required}' cannot be empty"
                );
            }
        }

        return true;
    }

    /**
     * Validate operation name
     *
     * @param mixed $value
     * @return bool
     */
    private static function validateOperation(mixed $value): bool
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(
                'Operation name must be a string, got ' . gettype($value)
            );
        }

        $validOperations = array_map(
            fn(GenAiOperations $op) => $op->value,
            GenAiOperations::cases()
        );

        if (!in_array($value, $validOperations, true)) {
            throw new InvalidArgumentException(
                "Invalid operation name '{$value}'. Must be one of: " . implode(', ', $validOperations)
            );
        }

        return true;
    }

    /**
     * Validate provider name
     *
     * @param mixed $value
     * @return bool
     */
    private static function validateProvider(mixed $value): bool
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(
                'Provider name must be a string, got ' . gettype($value)
            );
        }

        $validProviders = array_map(
            fn(GenAiProviders $provider) => $provider->value,
            GenAiProviders::cases()
        );

        if (!in_array($value, $validProviders, true)) {
            throw new InvalidArgumentException(
                "Invalid provider name '{$value}'. Must be one of: " . implode(', ', $validProviders)
            );
        }

        return true;
    }

    /**
     * Validate string value
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    private static function validateString(string $name, mixed $value): bool
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be a string, got " . gettype($value)
            );
        }

        if (trim($value) === '') {
            throw new InvalidArgumentException(
                "Attribute '{$name}' cannot be an empty string"
            );
        }

        return true;
    }

    /**
     * Validate integer value
     *
     * @param string $name
     * @param mixed $value
     * @param int|null $min
     * @param int|null $max
     * @return bool
     */
    private static function validateInteger(string $name, mixed $value, ?int $min = null, ?int $max = null): bool
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be an integer, got " . gettype($value)
            );
        }

        if ($min !== null && $value < $min) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be >= {$min}, got {$value}"
            );
        }

        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be <= {$max}, got {$value}"
            );
        }

        return true;
    }

    /**
     * Validate float value
     *
     * @param string $name
     * @param mixed $value
     * @param float|null $min
     * @param float|null $max
     * @return bool
     */
    private static function validateFloat(string $name, mixed $value, ?float $min = null, ?float $max = null): bool
    {
        if (!is_float($value) && !is_int($value)) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be a number, got " . gettype($value)
            );
        }

        $floatValue = (float) $value;

        if ($min !== null && $floatValue < $min) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be >= {$min}, got {$floatValue}"
            );
        }

        if ($max !== null && $floatValue > $max) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be <= {$max}, got {$floatValue}"
            );
        }

        return true;
    }

    /**
     * Validate array value
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    private static function validateArray(string $name, mixed $value): bool
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be an array, got " . gettype($value)
            );
        }

        return true;
    }

    /**
     * Validate string or array value
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    private static function validateStringOrArray(string $name, mixed $value): bool
    {
        if (!is_string($value) && !is_array($value)) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be a string or array, got " . gettype($value)
            );
        }

        return true;
    }

    /**
     * Validate messages array
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    private static function validateMessages(string $name, mixed $value): bool
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                "Attribute '{$name}' must be an array, got " . gettype($value)
            );
        }

        foreach ($value as $index => $message) {
            if (!is_array($message)) {
                throw new InvalidArgumentException(
                    "Attribute '{$name}[{$index}]' must be an array (message object)"
                );
            }

            if (!isset($message['role'])) {
                throw new InvalidArgumentException(
                    "Attribute '{$name}[{$index}]' must have a 'role' field"
                );
            }

            if (!isset($message['content'])) {
                throw new InvalidArgumentException(
                    "Attribute '{$name}[{$index}]' must have a 'content' field"
                );
            }

            $validRoles = ['system', 'user', 'assistant', 'tool'];
            if (!in_array($message['role'], $validRoles, true)) {
                throw new InvalidArgumentException(
                    "Attribute '{$name}[{$index}]' has invalid role '{$message['role']}'. Must be one of: " . implode(', ', $validRoles)
                );
            }
        }

        return true;
    }

    /**
     * Sanitize attributes by removing sensitive data if needed
     *
     * @param array<string, mixed> $attributes
     * @param bool $redactSensitive
     * @return array<string, mixed>
     */
    public static function sanitize(array $attributes, bool $redactSensitive = true): array
    {
        if (!$redactSensitive) {
            return $attributes;
        }

        $sanitized = $attributes;

        foreach (GenAiAttributes::getSensitiveAttributes() as $sensitiveAttr) {
            if (isset($sanitized[$sensitiveAttr])) {
                $sanitized[$sensitiveAttr] = '[REDACTED]';
            }
        }

        return $sanitized;
    }

    /**
     * Extract only GenAI attributes from a mixed attribute set
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public static function filterGenAiAttributes(array $attributes): array
    {
        return array_filter(
            $attributes,
            fn(string $key) => str_starts_with($key, 'gen_ai.'),
            ARRAY_FILTER_USE_KEY
        );
    }
}
