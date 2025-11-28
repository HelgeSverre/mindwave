<?php

declare(strict_types=1);

use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiOperations;
use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiProviders;

describe('GenAiProviders', function () {
    describe('Enum Cases', function () {
        it('defines all expected providers', function () {
            $cases = GenAiProviders::cases();

            expect($cases)->toHaveCount(10);
            expect(GenAiProviders::OPENAI->value)->toBe('openai');
            expect(GenAiProviders::ANTHROPIC->value)->toBe('anthropic');
            expect(GenAiProviders::MISTRAL_AI->value)->toBe('mistral_ai');
            expect(GenAiProviders::GCP_GEMINI->value)->toBe('gcp.gemini');
            expect(GenAiProviders::GCP_VERTEX_AI->value)->toBe('gcp.vertex_ai');
            expect(GenAiProviders::AWS_BEDROCK->value)->toBe('aws.bedrock');
            expect(GenAiProviders::COHERE->value)->toBe('cohere');
            expect(GenAiProviders::AZURE_OPENAI->value)->toBe('azure.openai');
            expect(GenAiProviders::HUGGING_FACE->value)->toBe('huggingface');
            expect(GenAiProviders::OLLAMA->value)->toBe('ollama');
        });
    });

    describe('getDefaultServerAddress', function () {
        it('returns correct server addresses', function () {
            expect(GenAiProviders::OPENAI->getDefaultServerAddress())->toBe('api.openai.com');
            expect(GenAiProviders::ANTHROPIC->getDefaultServerAddress())->toBe('api.anthropic.com');
            expect(GenAiProviders::MISTRAL_AI->getDefaultServerAddress())->toBe('api.mistral.ai');
            expect(GenAiProviders::GCP_GEMINI->getDefaultServerAddress())->toBe('generativelanguage.googleapis.com');
            expect(GenAiProviders::COHERE->getDefaultServerAddress())->toBe('api.cohere.ai');
            expect(GenAiProviders::AZURE_OPENAI->getDefaultServerAddress())->toBe('openai.azure.com');
            expect(GenAiProviders::HUGGING_FACE->getDefaultServerAddress())->toBe('api-inference.huggingface.co');
            expect(GenAiProviders::OLLAMA->getDefaultServerAddress())->toBe('localhost');
        });
    });

    describe('getDefaultServerPort', function () {
        it('returns 443 for cloud providers', function () {
            expect(GenAiProviders::OPENAI->getDefaultServerPort())->toBe(443);
            expect(GenAiProviders::ANTHROPIC->getDefaultServerPort())->toBe(443);
            expect(GenAiProviders::MISTRAL_AI->getDefaultServerPort())->toBe(443);
            expect(GenAiProviders::GCP_GEMINI->getDefaultServerPort())->toBe(443);
            expect(GenAiProviders::COHERE->getDefaultServerPort())->toBe(443);
        });

        it('returns 11434 for Ollama', function () {
            expect(GenAiProviders::OLLAMA->getDefaultServerPort())->toBe(11434);
        });
    });

    describe('getDisplayName', function () {
        it('returns human-readable display names', function () {
            expect(GenAiProviders::OPENAI->getDisplayName())->toBe('OpenAI');
            expect(GenAiProviders::ANTHROPIC->getDisplayName())->toBe('Anthropic');
            expect(GenAiProviders::MISTRAL_AI->getDisplayName())->toBe('Mistral AI');
            expect(GenAiProviders::GCP_GEMINI->getDisplayName())->toBe('Google Gemini');
            expect(GenAiProviders::GCP_VERTEX_AI->getDisplayName())->toBe('Google Vertex AI');
            expect(GenAiProviders::AWS_BEDROCK->getDisplayName())->toBe('AWS Bedrock');
            expect(GenAiProviders::COHERE->getDisplayName())->toBe('Cohere');
            expect(GenAiProviders::AZURE_OPENAI->getDisplayName())->toBe('Azure OpenAI');
            expect(GenAiProviders::HUGGING_FACE->getDisplayName())->toBe('Hugging Face');
            expect(GenAiProviders::OLLAMA->getDisplayName())->toBe('Ollama');
        });
    });

    describe('isCloudProvider', function () {
        it('returns true for cloud providers', function () {
            expect(GenAiProviders::OPENAI->isCloudProvider())->toBeTrue();
            expect(GenAiProviders::ANTHROPIC->isCloudProvider())->toBeTrue();
            expect(GenAiProviders::MISTRAL_AI->isCloudProvider())->toBeTrue();
            expect(GenAiProviders::GCP_GEMINI->isCloudProvider())->toBeTrue();
            expect(GenAiProviders::AWS_BEDROCK->isCloudProvider())->toBeTrue();
            expect(GenAiProviders::AZURE_OPENAI->isCloudProvider())->toBeTrue();
        });

        it('returns false for self-hosted providers', function () {
            expect(GenAiProviders::OLLAMA->isCloudProvider())->toBeFalse();
        });
    });

    describe('isSelfHosted', function () {
        it('returns true for Ollama', function () {
            expect(GenAiProviders::OLLAMA->isSelfHosted())->toBeTrue();
        });

        it('returns false for cloud providers', function () {
            expect(GenAiProviders::OPENAI->isSelfHosted())->toBeFalse();
            expect(GenAiProviders::ANTHROPIC->isSelfHosted())->toBeFalse();
            expect(GenAiProviders::AZURE_OPENAI->isSelfHosted())->toBeFalse();
        });
    });

    describe('getSupportedOperations', function () {
        it('returns full operation set for OpenAI', function () {
            $ops = GenAiProviders::OPENAI->getSupportedOperations();

            expect($ops)->toContain(GenAiOperations::CHAT);
            expect($ops)->toContain(GenAiOperations::TEXT_COMPLETION);
            expect($ops)->toContain(GenAiOperations::EMBEDDINGS);
            expect($ops)->toContain(GenAiOperations::IMAGE_GENERATION);
            expect($ops)->toContain(GenAiOperations::AUDIO_TRANSCRIPTION);
            expect($ops)->toContain(GenAiOperations::TEXT_TO_SPEECH);
        });

        it('returns only chat for Anthropic', function () {
            $ops = GenAiProviders::ANTHROPIC->getSupportedOperations();

            expect($ops)->toContain(GenAiOperations::CHAT);
            expect($ops)->toHaveCount(1);
        });

        it('returns chat and embeddings for Mistral', function () {
            $ops = GenAiProviders::MISTRAL_AI->getSupportedOperations();

            expect($ops)->toContain(GenAiOperations::CHAT);
            expect($ops)->toContain(GenAiOperations::EMBEDDINGS);
            expect($ops)->toHaveCount(2);
        });
    });

    describe('supportsOperation', function () {
        it('returns true for supported operations', function () {
            expect(GenAiProviders::OPENAI->supportsOperation(GenAiOperations::CHAT))->toBeTrue();
            expect(GenAiProviders::OPENAI->supportsOperation(GenAiOperations::EMBEDDINGS))->toBeTrue();
            expect(GenAiProviders::ANTHROPIC->supportsOperation(GenAiOperations::CHAT))->toBeTrue();
        });

        it('returns false for unsupported operations', function () {
            expect(GenAiProviders::ANTHROPIC->supportsOperation(GenAiOperations::EMBEDDINGS))->toBeFalse();
            expect(GenAiProviders::ANTHROPIC->supportsOperation(GenAiOperations::IMAGE_GENERATION))->toBeFalse();
            expect(GenAiProviders::MISTRAL_AI->supportsOperation(GenAiOperations::IMAGE_GENERATION))->toBeFalse();
        });
    });

    describe('fromString', function () {
        it('returns provider from valid string', function () {
            expect(GenAiProviders::fromString('openai'))->toBe(GenAiProviders::OPENAI);
            expect(GenAiProviders::fromString('anthropic'))->toBe(GenAiProviders::ANTHROPIC);
            expect(GenAiProviders::fromString('mistral_ai'))->toBe(GenAiProviders::MISTRAL_AI);
        });

        it('handles case-insensitive input', function () {
            expect(GenAiProviders::fromString('OPENAI'))->toBe(GenAiProviders::OPENAI);
            expect(GenAiProviders::fromString('OpenAI'))->toBe(GenAiProviders::OPENAI);
        });

        it('returns null for invalid string', function () {
            expect(GenAiProviders::fromString('invalid'))->toBeNull();
            expect(GenAiProviders::fromString('unknown_provider'))->toBeNull();
            expect(GenAiProviders::fromString(''))->toBeNull();
        });
    });

    describe('detectFromModel', function () {
        it('detects OpenAI from model names', function () {
            expect(GenAiProviders::detectFromModel('gpt-4'))->toBe(GenAiProviders::OPENAI);
            expect(GenAiProviders::detectFromModel('gpt-3.5-turbo'))->toBe(GenAiProviders::OPENAI);
            expect(GenAiProviders::detectFromModel('text-embedding-ada-002'))->toBe(GenAiProviders::OPENAI);
            expect(GenAiProviders::detectFromModel('dall-e-3'))->toBe(GenAiProviders::OPENAI);
        });

        it('detects Anthropic from model names', function () {
            expect(GenAiProviders::detectFromModel('claude-3-opus'))->toBe(GenAiProviders::ANTHROPIC);
            expect(GenAiProviders::detectFromModel('claude-3-sonnet'))->toBe(GenAiProviders::ANTHROPIC);
            expect(GenAiProviders::detectFromModel('claude-2'))->toBe(GenAiProviders::ANTHROPIC);
        });

        it('detects Mistral from model names', function () {
            expect(GenAiProviders::detectFromModel('mistral-large'))->toBe(GenAiProviders::MISTRAL_AI);
            expect(GenAiProviders::detectFromModel('mistral-medium'))->toBe(GenAiProviders::MISTRAL_AI);
            expect(GenAiProviders::detectFromModel('mixtral-8x7b'))->toBe(GenAiProviders::MISTRAL_AI);
        });

        it('detects Google Gemini from model names', function () {
            expect(GenAiProviders::detectFromModel('gemini-pro'))->toBe(GenAiProviders::GCP_GEMINI);
            expect(GenAiProviders::detectFromModel('gemini-ultra'))->toBe(GenAiProviders::GCP_GEMINI);
        });

        it('detects Cohere from model names', function () {
            expect(GenAiProviders::detectFromModel('command-r'))->toBe(GenAiProviders::COHERE);
            expect(GenAiProviders::detectFromModel('embed-english'))->toBe(GenAiProviders::COHERE);
        });

        it('handles case-insensitive model names', function () {
            expect(GenAiProviders::detectFromModel('GPT-4'))->toBe(GenAiProviders::OPENAI);
            expect(GenAiProviders::detectFromModel('Claude-3-Opus'))->toBe(GenAiProviders::ANTHROPIC);
        });

        it('returns null for unknown models', function () {
            expect(GenAiProviders::detectFromModel('unknown-model'))->toBeNull();
            expect(GenAiProviders::detectFromModel('custom-llm'))->toBeNull();
            expect(GenAiProviders::detectFromModel(''))->toBeNull();
        });
    });
});
