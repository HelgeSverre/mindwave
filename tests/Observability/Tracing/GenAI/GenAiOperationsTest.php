<?php

declare(strict_types=1);

use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiOperations;

describe('GenAiOperations', function () {
    describe('Enum Cases', function () {
        it('defines all expected operations', function () {
            $cases = GenAiOperations::cases();

            expect($cases)->toHaveCount(8);
            expect(GenAiOperations::CHAT->value)->toBe('chat');
            expect(GenAiOperations::TEXT_COMPLETION->value)->toBe('text_completion');
            expect(GenAiOperations::EMBEDDINGS->value)->toBe('embeddings');
            expect(GenAiOperations::EXECUTE_TOOL->value)->toBe('execute_tool');
            expect(GenAiOperations::IMAGE_GENERATION->value)->toBe('image_generation');
            expect(GenAiOperations::AUDIO_TRANSCRIPTION->value)->toBe('audio_transcription');
            expect(GenAiOperations::AUDIO_TRANSLATION->value)->toBe('audio_translation');
            expect(GenAiOperations::TEXT_TO_SPEECH->value)->toBe('text_to_speech');
        });
    });

    describe('getChatOperations', function () {
        it('returns chat and text_completion', function () {
            $chatOps = GenAiOperations::getChatOperations();

            expect($chatOps)->toContain(GenAiOperations::CHAT);
            expect($chatOps)->toContain(GenAiOperations::TEXT_COMPLETION);
            expect($chatOps)->toHaveCount(2);
        });
    });

    describe('getVectorOperations', function () {
        it('returns embeddings operation', function () {
            $vectorOps = GenAiOperations::getVectorOperations();

            expect($vectorOps)->toContain(GenAiOperations::EMBEDDINGS);
            expect($vectorOps)->toHaveCount(1);
        });
    });

    describe('getAudioOperations', function () {
        it('returns all audio operations', function () {
            $audioOps = GenAiOperations::getAudioOperations();

            expect($audioOps)->toContain(GenAiOperations::AUDIO_TRANSCRIPTION);
            expect($audioOps)->toContain(GenAiOperations::AUDIO_TRANSLATION);
            expect($audioOps)->toContain(GenAiOperations::TEXT_TO_SPEECH);
            expect($audioOps)->toHaveCount(3);
        });
    });

    describe('isChat', function () {
        it('returns true for chat operations', function () {
            expect(GenAiOperations::CHAT->isChat())->toBeTrue();
            expect(GenAiOperations::TEXT_COMPLETION->isChat())->toBeTrue();
        });

        it('returns false for non-chat operations', function () {
            expect(GenAiOperations::EMBEDDINGS->isChat())->toBeFalse();
            expect(GenAiOperations::EXECUTE_TOOL->isChat())->toBeFalse();
            expect(GenAiOperations::IMAGE_GENERATION->isChat())->toBeFalse();
            expect(GenAiOperations::AUDIO_TRANSCRIPTION->isChat())->toBeFalse();
        });
    });

    describe('isVector', function () {
        it('returns true for vector operations', function () {
            expect(GenAiOperations::EMBEDDINGS->isVector())->toBeTrue();
        });

        it('returns false for non-vector operations', function () {
            expect(GenAiOperations::CHAT->isVector())->toBeFalse();
            expect(GenAiOperations::TEXT_COMPLETION->isVector())->toBeFalse();
            expect(GenAiOperations::IMAGE_GENERATION->isVector())->toBeFalse();
        });
    });

    describe('isAudio', function () {
        it('returns true for audio operations', function () {
            expect(GenAiOperations::AUDIO_TRANSCRIPTION->isAudio())->toBeTrue();
            expect(GenAiOperations::AUDIO_TRANSLATION->isAudio())->toBeTrue();
            expect(GenAiOperations::TEXT_TO_SPEECH->isAudio())->toBeTrue();
        });

        it('returns false for non-audio operations', function () {
            expect(GenAiOperations::CHAT->isAudio())->toBeFalse();
            expect(GenAiOperations::EMBEDDINGS->isAudio())->toBeFalse();
            expect(GenAiOperations::IMAGE_GENERATION->isAudio())->toBeFalse();
        });
    });

    describe('supportsTokenUsage', function () {
        it('returns true for operations that track tokens', function () {
            expect(GenAiOperations::CHAT->supportsTokenUsage())->toBeTrue();
            expect(GenAiOperations::TEXT_COMPLETION->supportsTokenUsage())->toBeTrue();
            expect(GenAiOperations::EMBEDDINGS->supportsTokenUsage())->toBeTrue();
        });

        it('returns false for operations that do not track tokens', function () {
            expect(GenAiOperations::EXECUTE_TOOL->supportsTokenUsage())->toBeFalse();
            expect(GenAiOperations::IMAGE_GENERATION->supportsTokenUsage())->toBeFalse();
            expect(GenAiOperations::AUDIO_TRANSCRIPTION->supportsTokenUsage())->toBeFalse();
            expect(GenAiOperations::AUDIO_TRANSLATION->supportsTokenUsage())->toBeFalse();
            expect(GenAiOperations::TEXT_TO_SPEECH->supportsTokenUsage())->toBeFalse();
        });
    });

    describe('getDescription', function () {
        it('returns human-readable description for all operations', function () {
            expect(GenAiOperations::CHAT->getDescription())->toBe('Chat completion with conversational models');
            expect(GenAiOperations::TEXT_COMPLETION->getDescription())->toBe('Text completion generation');
            expect(GenAiOperations::EMBEDDINGS->getDescription())->toBe('Vector embeddings generation');
            expect(GenAiOperations::EXECUTE_TOOL->getDescription())->toBe('Tool or function execution');
            expect(GenAiOperations::IMAGE_GENERATION->getDescription())->toBe('Image generation from text');
            expect(GenAiOperations::AUDIO_TRANSCRIPTION->getDescription())->toBe('Speech-to-text transcription');
            expect(GenAiOperations::AUDIO_TRANSLATION->getDescription())->toBe('Audio translation');
            expect(GenAiOperations::TEXT_TO_SPEECH->getDescription())->toBe('Text-to-speech synthesis');
        });
    });
});
