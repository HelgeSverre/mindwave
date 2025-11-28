<?php

declare(strict_types=1);

use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiAttributes;
use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiAttributeValidator;

describe('GenAiAttributeValidator', function () {
    describe('validate', function () {
        it('accepts null values for any attribute', function () {
            expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE, null))->toBeTrue();
            expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_MODEL, null))->toBeTrue();
            expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS, null))->toBeTrue();
        });

        describe('Operation Name Validation', function () {
            it('accepts valid operation names', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_OPERATION_NAME, 'chat'))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_OPERATION_NAME, 'text_completion'))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_OPERATION_NAME, 'embeddings'))->toBeTrue();
            });

            it('rejects invalid operation names', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_OPERATION_NAME, 'invalid_op'))
                    ->toThrow(InvalidArgumentException::class, 'Invalid operation name');
            });

            it('rejects non-string operation names', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_OPERATION_NAME, 123))
                    ->toThrow(InvalidArgumentException::class, 'must be a string');
            });
        });

        describe('Provider Name Validation', function () {
            it('accepts valid provider names', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_PROVIDER_NAME, 'openai'))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_PROVIDER_NAME, 'anthropic'))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_PROVIDER_NAME, 'mistral_ai'))->toBeTrue();
            });

            it('rejects invalid provider names', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_PROVIDER_NAME, 'invalid_provider'))
                    ->toThrow(InvalidArgumentException::class, 'Invalid provider name');
            });

            it('rejects non-string provider names', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_PROVIDER_NAME, ['openai']))
                    ->toThrow(InvalidArgumentException::class, 'must be a string');
            });
        });

        describe('String Attribute Validation', function () {
            it('accepts valid strings', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_MODEL, 'gpt-4'))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_RESPONSE_ID, 'chatcmpl-123'))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::SERVER_ADDRESS, 'api.openai.com'))->toBeTrue();
            });

            it('rejects empty strings', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_MODEL, ''))
                    ->toThrow(InvalidArgumentException::class, 'cannot be an empty string');
            });

            it('rejects whitespace-only strings', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_MODEL, '   '))
                    ->toThrow(InvalidArgumentException::class, 'cannot be an empty string');
            });

            it('rejects non-string values', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_MODEL, 123))
                    ->toThrow(InvalidArgumentException::class, 'must be a string');
            });
        });

        describe('Float Attribute Validation', function () {
            it('accepts valid temperature values (0.0 to 2.0)', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE, 0.0))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE, 1.0))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE, 2.0))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE, 0.7))->toBeTrue();
            });

            it('accepts integers as floats', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE, 1))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TOP_P, 0))->toBeTrue();
            });

            it('rejects temperature below minimum', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE, -0.1))
                    ->toThrow(InvalidArgumentException::class, 'must be >= 0');
            });

            it('rejects temperature above maximum', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE, 2.5))
                    ->toThrow(InvalidArgumentException::class, 'must be <= 2');
            });

            it('accepts valid penalty values (-2.0 to 2.0)', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_FREQUENCY_PENALTY, -2.0))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_FREQUENCY_PENALTY, 0.0))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_FREQUENCY_PENALTY, 2.0))->toBeTrue();
            });

            it('rejects penalty below minimum', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_FREQUENCY_PENALTY, -3.0))
                    ->toThrow(InvalidArgumentException::class, 'must be >= -2');
            });

            it('rejects non-numeric values', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE, 'high'))
                    ->toThrow(InvalidArgumentException::class, 'must be a number');
            });
        });

        describe('Integer Attribute Validation', function () {
            it('accepts valid positive integers', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS, 100))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS, 0))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS, 1000))->toBeTrue();
            });

            it('rejects negative integers', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS, -1))
                    ->toThrow(InvalidArgumentException::class, 'must be >= 0');
            });

            it('rejects non-integer values', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS, 10.5))
                    ->toThrow(InvalidArgumentException::class, 'must be an integer');
            });

            it('rejects string numbers', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS, '100'))
                    ->toThrow(InvalidArgumentException::class, 'must be an integer');
            });
        });

        describe('Array Attribute Validation', function () {
            it('accepts valid arrays for finish reasons', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS, ['stop']))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS, ['stop', 'length']))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS, []))->toBeTrue();
            });

            it('accepts valid arrays for stop sequences', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_STOP_SEQUENCES, ['END']))->toBeTrue();
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_REQUEST_STOP_SEQUENCES, ['END', 'STOP']))->toBeTrue();
            });

            it('rejects non-array values', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS, 'stop'))
                    ->toThrow(InvalidArgumentException::class, 'must be an array');
            });
        });

        describe('Message Validation', function () {
            it('accepts valid message arrays', function () {
                $messages = [
                    ['role' => 'user', 'content' => 'Hello'],
                    ['role' => 'assistant', 'content' => 'Hi there!'],
                ];
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_INPUT_MESSAGES, $messages))->toBeTrue();
            });

            it('accepts all valid roles', function () {
                $messages = [
                    ['role' => 'system', 'content' => 'You are helpful'],
                    ['role' => 'user', 'content' => 'Hello'],
                    ['role' => 'assistant', 'content' => 'Hi!'],
                    ['role' => 'tool', 'content' => '{"result": 42}'],
                ];
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_INPUT_MESSAGES, $messages))->toBeTrue();
            });

            it('accepts empty message arrays', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_INPUT_MESSAGES, []))->toBeTrue();
            });

            it('rejects non-array messages', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_INPUT_MESSAGES, 'hello'))
                    ->toThrow(InvalidArgumentException::class, 'must be an array');
            });

            it('rejects messages with missing role', function () {
                $messages = [['content' => 'Hello']];
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_INPUT_MESSAGES, $messages))
                    ->toThrow(InvalidArgumentException::class, "must have a 'role' field");
            });

            it('rejects messages with missing content', function () {
                $messages = [['role' => 'user']];
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_INPUT_MESSAGES, $messages))
                    ->toThrow(InvalidArgumentException::class, "must have a 'content' field");
            });

            it('rejects messages with invalid role', function () {
                $messages = [['role' => 'invalid', 'content' => 'Hello']];
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_INPUT_MESSAGES, $messages))
                    ->toThrow(InvalidArgumentException::class, 'has invalid role');
            });

            it('rejects non-array message items', function () {
                $messages = ['hello'];
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_INPUT_MESSAGES, $messages))
                    ->toThrow(InvalidArgumentException::class, 'must be an array (message object)');
            });
        });

        describe('String or Array Validation', function () {
            it('accepts strings for system instructions', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS, 'Be helpful'))->toBeTrue();
            });

            it('accepts arrays for system instructions', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS, ['Be helpful', 'Be concise']))->toBeTrue();
            });

            it('accepts strings for tool call arguments', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS, '{"query": "test"}'))->toBeTrue();
            });

            it('accepts arrays for tool call arguments', function () {
                expect(GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS, ['query' => 'test']))->toBeTrue();
            });

            it('rejects non-string and non-array values', function () {
                expect(fn () => GenAiAttributeValidator::validate(GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS, 123))
                    ->toThrow(InvalidArgumentException::class, 'must be a string or array');
            });
        });

        it('accepts unknown attributes', function () {
            expect(GenAiAttributeValidator::validate('custom.attribute', 'any value'))->toBeTrue();
            expect(GenAiAttributeValidator::validate('unknown.attr', 123))->toBeTrue();
        });
    });

    describe('validateBatch', function () {
        it('validates multiple attributes at once', function () {
            $attributes = [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_PROVIDER_NAME => 'openai',
                GenAiAttributes::GEN_AI_REQUEST_MODEL => 'gpt-4',
                GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE => 0.7,
                GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS => 100,
            ];

            expect(GenAiAttributeValidator::validateBatch($attributes))->toBeTrue();
        });

        it('throws on first invalid attribute', function () {
            $attributes = [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE => 5.0, // Invalid
                GenAiAttributes::GEN_AI_PROVIDER_NAME => 'openai',
            ];

            expect(fn () => GenAiAttributeValidator::validateBatch($attributes))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('validateRequired', function () {
        it('passes when all required attributes present', function () {
            $attributes = [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_PROVIDER_NAME => 'openai',
                GenAiAttributes::GEN_AI_REQUEST_MODEL => 'gpt-4',
            ];

            expect(GenAiAttributeValidator::validateRequired($attributes))->toBeTrue();
        });

        it('throws when required attribute missing', function () {
            $attributes = [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_PROVIDER_NAME => 'openai',
                // Missing GEN_AI_REQUEST_MODEL
            ];

            expect(fn () => GenAiAttributeValidator::validateRequired($attributes))
                ->toThrow(InvalidArgumentException::class, 'is missing');
        });

        it('throws when required attribute is null', function () {
            $attributes = [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_PROVIDER_NAME => null, // isset() returns false for null
                GenAiAttributes::GEN_AI_REQUEST_MODEL => 'gpt-4',
            ];

            // Note: isset() returns false for null values, so it's treated as "missing"
            expect(fn () => GenAiAttributeValidator::validateRequired($attributes))
                ->toThrow(InvalidArgumentException::class, 'is missing');
        });

        it('throws when required attribute is empty string', function () {
            $attributes = [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_PROVIDER_NAME => 'openai',
                GenAiAttributes::GEN_AI_REQUEST_MODEL => '',
            ];

            expect(fn () => GenAiAttributeValidator::validateRequired($attributes))
                ->toThrow(InvalidArgumentException::class, 'cannot be empty');
        });
    });

    describe('sanitize', function () {
        it('redacts sensitive attributes by default', function () {
            $attributes = [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS => 'Secret prompt',
                GenAiAttributes::GEN_AI_INPUT_MESSAGES => [['role' => 'user', 'content' => 'Secret']],
                GenAiAttributes::GEN_AI_OUTPUT_MESSAGES => [['role' => 'assistant', 'content' => 'Response']],
            ];

            $sanitized = GenAiAttributeValidator::sanitize($attributes);

            expect($sanitized[GenAiAttributes::GEN_AI_OPERATION_NAME])->toBe('chat');
            expect($sanitized[GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS])->toBe('[REDACTED]');
            expect($sanitized[GenAiAttributes::GEN_AI_INPUT_MESSAGES])->toBe('[REDACTED]');
            expect($sanitized[GenAiAttributes::GEN_AI_OUTPUT_MESSAGES])->toBe('[REDACTED]');
        });

        it('preserves all attributes when redaction disabled', function () {
            $attributes = [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS => 'Secret prompt',
            ];

            $sanitized = GenAiAttributeValidator::sanitize($attributes, redactSensitive: false);

            expect($sanitized[GenAiAttributes::GEN_AI_OPERATION_NAME])->toBe('chat');
            expect($sanitized[GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS])->toBe('Secret prompt');
        });

        it('does not modify original array', function () {
            $original = [
                GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS => 'Secret prompt',
            ];

            GenAiAttributeValidator::sanitize($original);

            expect($original[GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS])->toBe('Secret prompt');
        });
    });

    describe('filterGenAiAttributes', function () {
        it('extracts only gen_ai prefixed attributes', function () {
            $mixed = [
                GenAiAttributes::GEN_AI_OPERATION_NAME => 'chat',
                GenAiAttributes::GEN_AI_PROVIDER_NAME => 'openai',
                GenAiAttributes::SERVER_ADDRESS => 'api.openai.com',
                'custom.attribute' => 'value',
                'http.method' => 'POST',
            ];

            $filtered = GenAiAttributeValidator::filterGenAiAttributes($mixed);

            expect($filtered)->toHaveKey(GenAiAttributes::GEN_AI_OPERATION_NAME);
            expect($filtered)->toHaveKey(GenAiAttributes::GEN_AI_PROVIDER_NAME);
            expect($filtered)->not->toHaveKey(GenAiAttributes::SERVER_ADDRESS);
            expect($filtered)->not->toHaveKey('custom.attribute');
            expect($filtered)->not->toHaveKey('http.method');
        });

        it('returns empty array when no gen_ai attributes', function () {
            $attributes = [
                'http.method' => 'POST',
                'http.status_code' => 200,
            ];

            $filtered = GenAiAttributeValidator::filterGenAiAttributes($attributes);

            expect($filtered)->toBeEmpty();
        });
    });
});
