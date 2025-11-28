<?php

declare(strict_types=1);

use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Document\Loaders\HtmlLoader;

describe('HtmlLoader', function () {
    beforeEach(function () {
        $this->loader = new HtmlLoader();
    });

    it('extracts text from simple HTML', function () {
        $html = '<p>Hello, world!</p>';

        $document = $this->loader->load($html);

        expect($document)->toBeInstanceOf(Document::class);
        expect($document->content())->toContain('Hello, world!');
    });

    it('removes script tags', function () {
        $html = '<p>Text</p><script>alert("malicious")</script>';

        $document = $this->loader->load($html);

        expect($document->content())->not->toContain('alert');
        expect($document->content())->not->toContain('malicious');
        expect($document->content())->toContain('Text');
    });

    it('removes style tags', function () {
        $html = '<style>.hidden { display: none; }</style><p>Visible</p>';

        $document = $this->loader->load($html);

        expect($document->content())->not->toContain('hidden');
        expect($document->content())->not->toContain('display');
        expect($document->content())->toContain('Visible');
    });

    it('removes link tags', function () {
        $html = '<link rel="stylesheet" href="style.css"><p>Content</p>';

        $document = $this->loader->load($html);

        expect($document->content())->not->toContain('stylesheet');
        expect($document->content())->toContain('Content');
    });

    it('removes head section', function () {
        $html = '<head><title>Page Title</title><meta charset="utf-8"></head><body>Body</body>';

        $document = $this->loader->load($html);

        expect($document->content())->not->toContain('charset');
        expect($document->content())->toContain('Body');
    });

    it('removes noscript tags', function () {
        $html = '<noscript>JavaScript required</noscript><p>Main content</p>';

        $document = $this->loader->load($html);

        expect($document->content())->not->toContain('JavaScript required');
        expect($document->content())->toContain('Main content');
    });

    it('removes SVG elements', function () {
        $html = '<svg><circle r="50"/></svg><p>Text</p>';

        $document = $this->loader->load($html);

        expect($document->content())->not->toContain('circle');
        expect($document->content())->toContain('Text');
    });

    it('extracts text from nested elements', function () {
        $html = '<div><p>Paragraph <span>with span</span></p></div>';

        $document = $this->loader->load($html);

        expect($document->content())->toContain('Paragraph');
        expect($document->content())->toContain('with span');
    });

    it('handles empty HTML', function () {
        $document = $this->loader->load('');

        expect($document)->toBeInstanceOf(Document::class);
        expect($document->content())->toBe('');
    });

    it('includes provided metadata', function () {
        $meta = ['source' => 'web', 'url' => 'http://example.com'];

        $document = $this->loader->load('<p>Content</p>', $meta);

        expect($document->metadata())->toBe($meta);
    });

    it('normalizes whitespace', function () {
        $html = '<p>Multiple    spaces   here</p>';

        $document = $this->loader->load($html);

        expect($document->content())->not->toContain('    ');
    });

    it('extracts text from lists', function () {
        $html = '<ul><li>Item 1</li><li>Item 2</li></ul>';

        $document = $this->loader->load($html);

        expect($document->content())->toContain('Item 1');
        expect($document->content())->toContain('Item 2');
    });

    it('extracts text from tables', function () {
        $html = '<table><tr><td>Cell 1</td><td>Cell 2</td></tr></table>';

        $document = $this->loader->load($html);

        expect($document->content())->toContain('Cell 1');
        expect($document->content())->toContain('Cell 2');
    });

    it('handles anchor tags', function () {
        $html = '<a href="http://example.com">Link text</a>';

        $document = $this->loader->load($html);

        expect($document->content())->toContain('Link text');
        expect($document->content())->not->toContain('http://example.com');
    });

    it('handles unicode content in HTML', function () {
        $html = '<p>日本語テキスト</p><p>Emoji 🎉</p>';

        $document = $this->loader->load($html);

        expect($document->content())->toContain('日本語テキスト');
        expect($document->content())->toContain('Emoji');
    });

    it('handles malformed HTML', function () {
        $html = '<p>Unclosed paragraph<div>Nested wrong</p></div>';

        $document = $this->loader->load($html);

        expect($document)->toBeInstanceOf(Document::class);
        expect($document->content())->toContain('Unclosed');
    });

    it('handles HTML entities', function () {
        $html = '<p>&amp; &lt; &gt; &quot;</p>';

        $document = $this->loader->load($html);

        expect($document->content())->toContain('&');
        expect($document->content())->toContain('<');
        expect($document->content())->toContain('>');
    });

    it('handles real-world HTML structure', function () {
        $html = '<!DOCTYPE html>
            <html>
            <head>
                <title>Test Page</title>
                <script>console.log("test")</script>
                <style>body { margin: 0; }</style>
            </head>
            <body>
                <header><nav>Navigation</nav></header>
                <main>
                    <article>
                        <h1>Title</h1>
                        <p>Paragraph content.</p>
                    </article>
                </main>
                <footer>Footer content</footer>
            </body>
            </html>';

        $document = $this->loader->load($html);

        expect($document->content())->toContain('Navigation');
        expect($document->content())->toContain('Title');
        expect($document->content())->toContain('Paragraph content');
        expect($document->content())->toContain('Footer content');
        expect($document->content())->not->toContain('console.log');
        expect($document->content())->not->toContain('margin');
    });
});
