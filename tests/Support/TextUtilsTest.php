<?php

declare(strict_types=1);

use Mindwave\Mindwave\Support\TextUtils;

describe('TextUtils', function () {
    describe('normalizeWhitespace', function () {
        it('removes extra spaces', function () {
            $text = 'Hello    world   with   spaces';

            $result = TextUtils::normalizeWhitespace($text);

            expect($result)->toBe('Hello world with spaces');
        });

        it('removes leading whitespace', function () {
            $text = '   Leading spaces';

            $result = TextUtils::normalizeWhitespace($text);

            expect($result)->toBe('Leading spaces');
        });

        it('removes trailing whitespace', function () {
            $text = 'Trailing spaces   ';

            $result = TextUtils::normalizeWhitespace($text);

            expect($result)->toBe('Trailing spaces');
        });

        it('normalizes newlines', function () {
            $text = "Line 1\n\n\nLine 2";

            $result = TextUtils::normalizeWhitespace($text);

            expect($result)->toBe('Line 1 Line 2');
        });

        it('normalizes tabs', function () {
            $text = "Column1\t\tColumn2";

            $result = TextUtils::normalizeWhitespace($text);

            expect($result)->toBe('Column1 Column2');
        });

        it('handles empty string', function () {
            $result = TextUtils::normalizeWhitespace('');

            expect($result)->toBe('');
        });

        it('handles whitespace-only string', function () {
            $result = TextUtils::normalizeWhitespace('     ');

            expect($result)->toBe('');
        });

        it('preserves single spaces', function () {
            $text = 'Word1 Word2 Word3';

            $result = TextUtils::normalizeWhitespace($text);

            expect($result)->toBe('Word1 Word2 Word3');
        });
    });

    describe('combine', function () {
        it('combines lines with newlines', function () {
            $lines = ['Line 1', 'Line 2', 'Line 3'];

            $result = TextUtils::combine($lines);

            expect($result)->toBe("Line 1\nLine 2\nLine 3");
        });

        it('filters empty lines', function () {
            $lines = ['Line 1', '', 'Line 3', null, 'Line 5'];

            $result = TextUtils::combine($lines);

            expect($result)->toBe("Line 1\nLine 3\nLine 5");
        });

        it('handles empty array', function () {
            $result = TextUtils::combine([]);

            expect($result)->toBe('');
        });

        it('handles array of empty strings', function () {
            $lines = ['', '', ''];

            $result = TextUtils::combine($lines);

            expect($result)->toBe('');
        });

        it('handles single line', function () {
            $lines = ['Only one line'];

            $result = TextUtils::combine($lines);

            expect($result)->toBe('Only one line');
        });
    });

    describe('cleanHtml', function () {
        it('removes script tags', function () {
            $html = '<p>Text</p><script>alert("XSS")</script>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->not->toContain('alert');
            expect($result)->not->toContain('XSS');
            expect($result)->toContain('Text');
        });

        it('removes style tags', function () {
            $html = '<style>.hidden{display:none}</style><p>Visible</p>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->not->toContain('display');
            expect($result)->toContain('Visible');
        });

        it('removes link tags', function () {
            $html = '<link rel="stylesheet" href="style.css"><p>Content</p>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->not->toContain('stylesheet');
            expect($result)->toContain('Content');
        });

        it('removes head section', function () {
            $html = '<head><title>Title</title></head><body>Body</body>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->toContain('Body');
        });

        it('removes noscript tags', function () {
            $html = '<noscript>Enable JS</noscript><p>Content</p>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->not->toContain('Enable JS');
            expect($result)->toContain('Content');
        });

        it('removes template tags', function () {
            $html = '<template><div>Template content</div></template><p>Real</p>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->not->toContain('Template content');
            expect($result)->toContain('Real');
        });

        it('removes SVG elements', function () {
            $html = '<svg><circle r="50"/></svg><p>Text</p>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->not->toContain('circle');
            expect($result)->toContain('Text');
        });

        it('extracts text from paragraphs', function () {
            $html = '<p>Paragraph one.</p><p>Paragraph two.</p>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->toContain('Paragraph one');
            expect($result)->toContain('Paragraph two');
        });

        it('handles nested elements', function () {
            $html = '<div><p>Outer <span>inner</span> content</p></div>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->toContain('Outer');
            expect($result)->toContain('inner');
            expect($result)->toContain('content');
        });

        it('normalizes whitespace by default', function () {
            $html = '<p>Multiple    spaces</p>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->not->toContain('    ');
        });

        it('respects normalizeWhitespace option', function () {
            $html = '<p>Multiple    spaces</p>';

            $result = TextUtils::cleanHtml($html, normalizeWhitespace: false);

            // Without normalization, may preserve more structure
            expect($result)->toContain('Multiple');
            expect($result)->toContain('spaces');
        });

        it('handles empty HTML', function () {
            $result = TextUtils::cleanHtml('');

            expect($result)->toBe('');
        });

        it('handles whitespace-only HTML', function () {
            $result = TextUtils::cleanHtml('   ');

            expect($result)->toBe('');
        });

        it('extracts text from anchor tags', function () {
            $html = '<a href="http://example.com">Link text</a>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->toContain('Link text');
        });

        it('handles HTML entities', function () {
            $html = '<p>&amp; &lt; &gt;</p>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->toContain('&');
            expect($result)->toContain('<');
            expect($result)->toContain('>');
        });

        it('handles unicode content', function () {
            $html = '<p>日本語 🎉 العربية</p>';

            $result = TextUtils::cleanHtml($html);

            expect($result)->toContain('日本語');
        });

        it('accepts custom elements to remove', function () {
            $html = '<nav>Navigation</nav><main>Content</main>';

            $result = TextUtils::cleanHtml($html, elementsToRemove: ['nav']);

            expect($result)->not->toContain('Navigation');
            expect($result)->toContain('Content');
        });
    });
});
