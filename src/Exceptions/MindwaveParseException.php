<?php

namespace Mindwave\Mindwave\Exceptions;

use Exception;

/**
 * Exception thrown when parsing of LLM output fails.
 *
 * This exception is thrown when the output from an LLM cannot be parsed
 * into the expected format, such as when JSON parsing fails or when
 * the output doesn't match the expected schema.
 */
class MindwaveParseException extends Exception
{
    /**
     * The raw text that failed to parse.
     */
    protected string $rawText;

    /**
     * Create a new parse exception.
     *
     * @param  string  $message  The exception message
     * @param  string  $rawText  The raw text that failed to parse
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  The previous exception
     */
    public function __construct(
        string $message,
        string $rawText = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->rawText = $rawText;
    }

    /**
     * Get the raw text that failed to parse.
     */
    public function getRawText(): string
    {
        return $this->rawText;
    }

    /**
     * Create an exception for invalid JSON.
     */
    public static function invalidJson(string $rawText, ?string $jsonError = null): self
    {
        $message = 'Failed to parse LLM output as JSON.';
        if ($jsonError) {
            $message .= " JSON error: {$jsonError}";
        }

        return new self($message, $rawText);
    }

    /**
     * Create an exception for missing required property.
     */
    public static function missingProperty(string $property, string $rawText): self
    {
        return new self(
            "Required property '{$property}' is missing from the parsed output.",
            $rawText
        );
    }
}
