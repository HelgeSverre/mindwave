<?php

declare(strict_types=1);

use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiAttributes;

describe('GenAiAttributes', function () {
    describe('Constants', function () {
        it('defines operation name attribute', function () {
            expect(GenAiAttributes::GEN_AI_OPERATION_NAME)->toBe('gen_ai.operation.name');
        });

        it('defines provider name attribute', function () {
            expect(GenAiAttributes::GEN_AI_PROVIDER_NAME)->toBe('gen_ai.provider.name');
        });

        it('defines request model attribute', function () {
            expect(GenAiAttributes::GEN_AI_REQUEST_MODEL)->toBe('gen_ai.request.model');
        });

        it('defines all request parameter attributes', function () {
            expect(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE)->toBe('gen_ai.request.temperature');
            expect(GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS)->toBe('gen_ai.request.max_tokens');
            expect(GenAiAttributes::GEN_AI_REQUEST_TOP_P)->toBe('gen_ai.request.top_p');
            expect(GenAiAttributes::GEN_AI_REQUEST_TOP_K)->toBe('gen_ai.request.top_k');
            expect(GenAiAttributes::GEN_AI_REQUEST_FREQUENCY_PENALTY)->toBe('gen_ai.request.frequency_penalty');
            expect(GenAiAttributes::GEN_AI_REQUEST_PRESENCE_PENALTY)->toBe('gen_ai.request.presence_penalty');
            expect(GenAiAttributes::GEN_AI_REQUEST_STOP_SEQUENCES)->toBe('gen_ai.request.stop_sequences');
        });

        it('defines all response attributes', function () {
            expect(GenAiAttributes::GEN_AI_RESPONSE_ID)->toBe('gen_ai.response.id');
            expect(GenAiAttributes::GEN_AI_RESPONSE_MODEL)->toBe('gen_ai.response.model');
            expect(GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS)->toBe('gen_ai.response.finish_reasons');
        });

        it('defines all usage/token attributes', function () {
            expect(GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS)->toBe('gen_ai.usage.input_tokens');
            expect(GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS)->toBe('gen_ai.usage.output_tokens');
            expect(GenAiAttributes::GEN_AI_USAGE_TOTAL_TOKENS)->toBe('gen_ai.usage.total_tokens');
            expect(GenAiAttributes::GEN_AI_USAGE_CACHE_READ_TOKENS)->toBe('gen_ai.usage.cache_read_tokens');
            expect(GenAiAttributes::GEN_AI_USAGE_CACHE_CREATION_TOKENS)->toBe('gen_ai.usage.cache_creation_tokens');
        });

        it('defines content attributes', function () {
            expect(GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS)->toBe('gen_ai.system_instructions');
            expect(GenAiAttributes::GEN_AI_INPUT_MESSAGES)->toBe('gen_ai.input.messages');
            expect(GenAiAttributes::GEN_AI_OUTPUT_MESSAGES)->toBe('gen_ai.output.messages');
        });

        it('defines tool attributes', function () {
            expect(GenAiAttributes::GEN_AI_TOOL_CALL_NAME)->toBe('gen_ai.tool.call.name');
            expect(GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS)->toBe('gen_ai.tool.call.arguments');
            expect(GenAiAttributes::GEN_AI_TOOL_CALL_RESULT)->toBe('gen_ai.tool.call.result');
        });

        it('defines embeddings attributes', function () {
            expect(GenAiAttributes::GEN_AI_EMBEDDINGS_INPUT)->toBe('gen_ai.embeddings.input');
            expect(GenAiAttributes::GEN_AI_EMBEDDINGS_DIMENSION)->toBe('gen_ai.embeddings.dimension');
        });

        it('defines server attributes', function () {
            expect(GenAiAttributes::SERVER_ADDRESS)->toBe('server.address');
            expect(GenAiAttributes::SERVER_PORT)->toBe('server.port');
        });

        it('defines legacy attributes', function () {
            expect(GenAiAttributes::LLM_USAGE_TOTAL_TOKENS)->toBe('llm.usage.total_tokens');
        });
    });

    describe('getRequiredAttributes', function () {
        it('returns array of required attribute names', function () {
            $required = GenAiAttributes::getRequiredAttributes();

            expect($required)->toBeArray();
            expect($required)->toContain(GenAiAttributes::GEN_AI_OPERATION_NAME);
            expect($required)->toContain(GenAiAttributes::GEN_AI_PROVIDER_NAME);
            expect($required)->toContain(GenAiAttributes::GEN_AI_REQUEST_MODEL);
            expect($required)->toHaveCount(3);
        });
    });

    describe('getRequestAttributes', function () {
        it('returns array of request attribute names', function () {
            $attrs = GenAiAttributes::getRequestAttributes();

            expect($attrs)->toBeArray();
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_REQUEST_MODEL);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_REQUEST_MAX_TOKENS);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_REQUEST_TOP_P);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_REQUEST_TOP_K);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_REQUEST_FREQUENCY_PENALTY);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_REQUEST_PRESENCE_PENALTY);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_REQUEST_STOP_SEQUENCES);
        });
    });

    describe('getResponseAttributes', function () {
        it('returns array of response attribute names', function () {
            $attrs = GenAiAttributes::getResponseAttributes();

            expect($attrs)->toBeArray();
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_RESPONSE_ID);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_RESPONSE_MODEL);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_RESPONSE_FINISH_REASONS);
        });
    });

    describe('getUsageAttributes', function () {
        it('returns array of usage attribute names', function () {
            $attrs = GenAiAttributes::getUsageAttributes();

            expect($attrs)->toBeArray();
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_USAGE_OUTPUT_TOKENS);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_USAGE_TOTAL_TOKENS);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_USAGE_CACHE_READ_TOKENS);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_USAGE_CACHE_CREATION_TOKENS);
        });
    });

    describe('getSensitiveAttributes', function () {
        it('returns array of sensitive attribute names', function () {
            $attrs = GenAiAttributes::getSensitiveAttributes();

            expect($attrs)->toBeArray();
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_INPUT_MESSAGES);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_OUTPUT_MESSAGES);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_TOOL_CALL_RESULT);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_EMBEDDINGS_INPUT);
        });
    });

    describe('getToolAttributes', function () {
        it('returns array of tool attribute names', function () {
            $attrs = GenAiAttributes::getToolAttributes();

            expect($attrs)->toBeArray();
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_TOOL_CALL_NAME);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_TOOL_CALL_RESULT);
        });
    });

    describe('getEmbeddingsAttributes', function () {
        it('returns array of embeddings attribute names', function () {
            $attrs = GenAiAttributes::getEmbeddingsAttributes();

            expect($attrs)->toBeArray();
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_EMBEDDINGS_INPUT);
            expect($attrs)->toContain(GenAiAttributes::GEN_AI_EMBEDDINGS_DIMENSION);
        });
    });

    describe('isSensitive', function () {
        it('returns true for sensitive attributes', function () {
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_SYSTEM_INSTRUCTIONS))->toBeTrue();
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_INPUT_MESSAGES))->toBeTrue();
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_OUTPUT_MESSAGES))->toBeTrue();
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_TOOL_CALL_ARGUMENTS))->toBeTrue();
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_TOOL_CALL_RESULT))->toBeTrue();
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_EMBEDDINGS_INPUT))->toBeTrue();
        });

        it('returns false for non-sensitive attributes', function () {
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_OPERATION_NAME))->toBeFalse();
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_PROVIDER_NAME))->toBeFalse();
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_REQUEST_MODEL))->toBeFalse();
            expect(GenAiAttributes::isSensitive(GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS))->toBeFalse();
        });

        it('returns false for unknown attributes', function () {
            expect(GenAiAttributes::isSensitive('unknown.attribute'))->toBeFalse();
        });
    });

    describe('isRequired', function () {
        it('returns true for required attributes', function () {
            expect(GenAiAttributes::isRequired(GenAiAttributes::GEN_AI_OPERATION_NAME))->toBeTrue();
            expect(GenAiAttributes::isRequired(GenAiAttributes::GEN_AI_PROVIDER_NAME))->toBeTrue();
            expect(GenAiAttributes::isRequired(GenAiAttributes::GEN_AI_REQUEST_MODEL))->toBeTrue();
        });

        it('returns false for optional attributes', function () {
            expect(GenAiAttributes::isRequired(GenAiAttributes::GEN_AI_REQUEST_TEMPERATURE))->toBeFalse();
            expect(GenAiAttributes::isRequired(GenAiAttributes::GEN_AI_USAGE_INPUT_TOKENS))->toBeFalse();
            expect(GenAiAttributes::isRequired(GenAiAttributes::GEN_AI_RESPONSE_ID))->toBeFalse();
        });

        it('returns false for unknown attributes', function () {
            expect(GenAiAttributes::isRequired('unknown.attribute'))->toBeFalse();
        });
    });
});
