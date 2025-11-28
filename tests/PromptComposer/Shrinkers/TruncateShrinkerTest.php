<?php

declare(strict_types=1);

use Mindwave\Mindwave\PromptComposer\Shrinkers\TruncateShrinker;
use Mindwave\Mindwave\PromptComposer\Tokenizer\TiktokenTokenizer;

beforeEach(function () {
    $this->tokenizer = app(TiktokenTokenizer::class);
});

describe('TruncateShrinker', function () {
    describe('Basic functionality', function () {
        it('returns content unchanged when within token limit', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);
            $content = 'This is a short text.';

            $result = $shrinker->shrink($content, 100, 'gpt-4');

            expect($result)->toBe($content);
        });

        it('returns name as truncate', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);

            expect($shrinker->name())->toBe('truncate');
        });

        it('truncates content that exceeds token limit', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);
            $content = 'This is a longer text that needs to be truncated. It has multiple sentences. Each sentence adds more tokens.';

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            expect(strlen($result))->toBeLessThan(strlen($content));
            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(10);
        });
    });

    describe('Sentence-aware truncation', function () {
        it('truncates by complete sentences by default', function () {
            $shrinker = new TruncateShrinker($this->tokenizer, sentenceAware: true);
            $content = 'First sentence. Second sentence. Third sentence. Fourth sentence.';

            $result = $shrinker->shrink($content, 15, 'gpt-4');

            // Should end with a complete sentence
            expect($result)->toEndWith('.');
        });

        it('handles sentences ending with exclamation marks', function () {
            $shrinker = new TruncateShrinker($this->tokenizer, sentenceAware: true);
            $content = 'Hello world! This is exciting! More text here.';

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            // Should respect sentence boundaries
            expect(strlen($result))->toBeLessThan(strlen($content));
        });

        it('handles sentences ending with question marks', function () {
            $shrinker = new TruncateShrinker($this->tokenizer, sentenceAware: true);
            $content = 'Is this working? I hope so? Yes it is.';

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            expect(strlen($result))->toBeLessThan(strlen($content));
        });

        it('falls back to word truncation when first sentence exceeds limit', function () {
            $shrinker = new TruncateShrinker($this->tokenizer, sentenceAware: true);
            // One long sentence
            $content = 'This is a very long sentence that contains many words and will definitely exceed the token limit.';

            $result = $shrinker->shrink($content, 5, 'gpt-4');

            expect(strlen($result))->toBeLessThan(strlen($content));
            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(5);
        });
    });

    describe('Word-level truncation', function () {
        it('truncates by words when sentence-aware is disabled', function () {
            $shrinker = new TruncateShrinker($this->tokenizer, sentenceAware: false);
            $content = 'First word second word third word fourth word fifth word.';

            $result = $shrinker->shrink($content, 5, 'gpt-4');

            expect(strlen($result))->toBeLessThan(strlen($content));
            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(5);
        });

        it('handles single word that exceeds limit', function () {
            $shrinker = new TruncateShrinker($this->tokenizer, sentenceAware: false);
            $content = 'Supercalifragilisticexpialidocious is a long word.';

            $result = $shrinker->shrink($content, 1, 'gpt-4');

            // Should return empty or the first word if it fits
            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(1);
        });
    });

    describe('Edge cases', function () {
        it('handles empty string', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);

            $result = $shrinker->shrink('', 10, 'gpt-4');

            expect($result)->toBe('');
        });

        it('handles zero token limit', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);
            $content = 'Some text here.';

            $result = $shrinker->shrink($content, 0, 'gpt-4');

            expect($result)->toBe('');
        });

        it('handles content with only whitespace', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);
            $content = '   ';

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            expect($result)->toBe('   ');
        });

        it('handles content with special characters', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);
            $content = 'Hello @world! #testing 123. More content here.';

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(10);
        });

        it('handles unicode content', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);
            $content = 'Hello 世界! Bonjour le monde. 你好世界.';

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(10);
        });

        it('handles newlines in content', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);
            $content = "First paragraph.\n\nSecond paragraph.\n\nThird paragraph.";

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(10);
        });

        it('handles content exactly at token limit', function () {
            $shrinker = new TruncateShrinker($this->tokenizer);
            $content = 'Hello';
            $tokenCount = $this->tokenizer->count($content, 'gpt-4');

            $result = $shrinker->shrink($content, $tokenCount, 'gpt-4');

            expect($result)->toBe($content);
        });
    });
});
