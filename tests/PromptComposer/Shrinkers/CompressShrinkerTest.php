<?php

declare(strict_types=1);

use Mindwave\Mindwave\PromptComposer\Shrinkers\CompressShrinker;
use Mindwave\Mindwave\PromptComposer\Tokenizer\TiktokenTokenizer;

beforeEach(function () {
    $this->tokenizer = app(TiktokenTokenizer::class);
});

describe('CompressShrinker', function () {
    describe('Basic functionality', function () {
        it('returns content unchanged when within token limit', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = 'This is a short text.';

            $result = $shrinker->shrink($content, 100, 'gpt-4');

            expect($result)->toBe($content);
        });

        it('returns name as compress', function () {
            $shrinker = new CompressShrinker($this->tokenizer);

            expect($shrinker->name())->toBe('compress');
        });
    });

    describe('Whitespace removal', function () {
        it('removes extra spaces when compression needed', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            // Content with extra spaces that needs shrinking
            $content = 'Hello    world   with    extra   spaces   and   more   text   here   for   testing.';

            // Use a limit that forces compression
            $result = $shrinker->shrink($content, 5, 'gpt-4');

            // Should be compressed and not contain multiple consecutive spaces
            expect($result)->not->toMatch('/  /');
        });

        it('removes multiple newlines when compression needed', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = "First paragraph.\n\n\n\n\nSecond paragraph.\n\n\n\nThird paragraph with more text.";

            // Use limit that forces compression but allows some content
            $result = $shrinker->shrink($content, 10, 'gpt-4');

            // Multiple newlines should be reduced
            expect($result)->not->toContain("\n\n\n");
        });

        it('replaces tabs with spaces when compression needed', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = "Column1\tColumn2\tColumn3\tColumn4\tColumn5\tColumn6\tColumn7\tColumn8";

            // Force compression
            $result = $shrinker->shrink($content, 5, 'gpt-4');

            expect($result)->not->toContain("\t");
        });

        it('returns content unchanged when within limit', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = '   Hello world   ';

            // Large limit - no compression needed
            $result = $shrinker->shrink($content, 100, 'gpt-4');

            // Content returned unchanged when within limit
            expect($result)->toBe($content);
        });
    });

    describe('Markdown removal', function () {
        it('removes bold markers when compression needed', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            // Make content long enough that it needs markdown stripping
            $content = 'This is **bold** text and __also bold__ text. '.str_repeat('More content here. ', 10);

            // Force markdown removal by using a limit that's too small
            $result = $shrinker->shrink($content, 15, 'gpt-4');

            expect($result)->not->toContain('**');
            expect($result)->not->toContain('__');
        });

        it('preserves text after removing formatting', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = 'This is *italic* text. '.str_repeat('Word ', 20);

            // Use small limit
            $result = $shrinker->shrink($content, 10, 'gpt-4');

            // Text should be preserved (at least the beginning)
            expect($result)->toContain('This');
        });

        it('removes code blocks when compression needed', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = "Before code ```php\n<?php echo 'Hello';\n``` after code. ".str_repeat('More text. ', 10);

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            // Code blocks should be removed when compression is applied
            expect($result)->not->toContain('```');
        });

        it('removes inline code markers when compression needed', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = 'Use the `echo` command to print. '.str_repeat('Additional text. ', 10);

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            expect($result)->not->toContain('`');
        });

        it('removes header markers when compression needed', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = "# Title\n## Subtitle\n### Section\nContent here. ".str_repeat('More content. ', 10);

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            // Header markers (#) should be removed
            expect($result)->not->toMatch('/^#+\s/m');
        });

        it('removes link syntax when compression needed', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = 'Visit [Google](https://google.com) for more info. '.str_repeat('Extra text. ', 10);

            $result = $shrinker->shrink($content, 10, 'gpt-4');

            // Link brackets should be removed
            expect($result)->not->toContain('](');
        });
    });

    describe('Compression pipeline', function () {
        it('applies compression steps in order when needed', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = "# Header\n\n\n\n**Bold**   with   spaces   and [link](url). ".str_repeat('Extra text. ', 20);

            // Force compression with small limit
            $result = $shrinker->shrink($content, 10, 'gpt-4');

            // Should have applied compression pipeline
            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(10);
        });

        it('falls back to truncation when compression is not enough', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            // Plain text without markdown - can't be compressed, only truncated
            $content = 'This is a plain text without any markdown formatting that is quite long.';

            $result = $shrinker->shrink($content, 5, 'gpt-4');

            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(5);
        });
    });

    describe('Edge cases', function () {
        it('handles empty string', function () {
            $shrinker = new CompressShrinker($this->tokenizer);

            $result = $shrinker->shrink('', 10, 'gpt-4');

            expect($result)->toBe('');
        });

        it('handles zero token limit', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = 'Some text here.';

            $result = $shrinker->shrink($content, 0, 'gpt-4');

            expect($result)->toBe('');
        });

        it('handles content with only whitespace unchanged when within limit', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = '     ';

            // Whitespace content within limit is returned as-is
            $result = $shrinker->shrink($content, 100, 'gpt-4');

            expect($result)->toBe('     ');
        });

        it('compresses whitespace-only content when exceeding limit', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = str_repeat(' ', 1000);

            // Force compression
            $result = $shrinker->shrink($content, 1, 'gpt-4');

            // Should be trimmed/compressed to empty
            expect($result)->toBe('');
        });

        it('handles nested markdown', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = '**Bold with _nested italic_**';

            // Content within limit - returned unchanged
            $result = $shrinker->shrink($content, 50, 'gpt-4');

            expect($result)->toContain('Bold');
        });

        it('handles unicode content with compression', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = "# 你好世界\n\nThis is **中文** content. ".str_repeat('More. ', 20);

            // Force compression
            $result = $shrinker->shrink($content, 10, 'gpt-4');

            // Just verify it respects the token limit
            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(10);
        });

        it('handles content exactly at token limit', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = 'Hello';
            $tokenCount = $this->tokenizer->count($content, 'gpt-4');

            $result = $shrinker->shrink($content, $tokenCount, 'gpt-4');

            expect($result)->toBe($content);
        });

        it('handles very long content', function () {
            $shrinker = new CompressShrinker($this->tokenizer);
            $content = str_repeat('Word ', 1000);

            $result = $shrinker->shrink($content, 50, 'gpt-4');

            expect($this->tokenizer->count($result, 'gpt-4'))->toBeLessThanOrEqual(50);
        });
    });
});
