<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Document\Loaders\WebLoader;

describe('WebLoader', function () {
    beforeEach(function () {
        $this->loader = new WebLoader();
    });

    describe('URL Validation', function () {
        it('throws exception for invalid URL', function () {
            expect(fn () => $this->loader->load('not-a-valid-url'))
                ->toThrow(InvalidArgumentException::class, 'Invalid URL');
        });

        it('throws exception for empty string', function () {
            expect(fn () => $this->loader->load(''))
                ->toThrow(InvalidArgumentException::class, 'Invalid URL');
        });

        it('throws exception for path without protocol', function () {
            expect(fn () => $this->loader->load('/path/to/file'))
                ->toThrow(InvalidArgumentException::class, 'Invalid URL');
        });

        it('throws exception for malformed URL', function () {
            expect(fn () => $this->loader->load('http://'))
                ->toThrow(InvalidArgumentException::class, 'Invalid URL');
        });
    });

    describe('HTTP Requests', function () {
        it('loads content from valid URL', function () {
            Http::fake([
                'example.com/*' => Http::response('<html><body><p>Test content</p></body></html>', 200),
            ]);

            $document = $this->loader->load('https://example.com/page');

            expect($document)->toBeInstanceOf(Document::class);
            expect($document->content())->toContain('Test content');
        });

        it('returns null on HTTP failure', function () {
            Http::fake([
                'example.com/*' => Http::response('Not found', 404),
            ]);

            $document = $this->loader->load('https://example.com/missing');

            expect($document)->toBeNull();
        });

        it('returns null on server error', function () {
            Http::fake([
                'example.com/*' => Http::response('Internal error', 500),
            ]);

            $document = $this->loader->load('https://example.com/error');

            expect($document)->toBeNull();
        });

        it('includes URL in metadata', function () {
            Http::fake([
                'example.com/*' => Http::response('<p>Content</p>', 200),
            ]);

            $document = $this->loader->load('https://example.com/page');

            expect($document->metadata())->toHaveKey('url');
            expect($document->metadata()['url'])->toBe('https://example.com/page');
        });

        it('merges provided metadata', function () {
            Http::fake([
                'example.com/*' => Http::response('<p>Content</p>', 200),
            ]);

            $document = $this->loader->load('https://example.com/page', [
                'custom_key' => 'custom_value',
            ]);

            expect($document->metadata())->toHaveKey('url');
            expect($document->metadata())->toHaveKey('custom_key');
            expect($document->metadata()['custom_key'])->toBe('custom_value');
        });
    });

    describe('HTML Processing', function () {
        it('removes script tags from web content', function () {
            Http::fake([
                '*' => Http::response('<html><script>alert("XSS")</script><p>Safe</p></html>', 200),
            ]);

            $document = $this->loader->load('https://example.com');

            expect($document->content())->not->toContain('alert');
            expect($document->content())->toContain('Safe');
        });

        it('removes style tags from web content', function () {
            Http::fake([
                '*' => Http::response('<style>.hidden{}</style><p>Visible</p>', 200),
            ]);

            $document = $this->loader->load('https://example.com');

            expect($document->content())->not->toContain('.hidden');
            expect($document->content())->toContain('Visible');
        });

        it('extracts text from complex HTML', function () {
            Http::fake([
                '*' => Http::response('
                    <html>
                    <head><title>Page</title></head>
                    <body>
                        <nav>Navigation</nav>
                        <main>
                            <article>
                                <h1>Article Title</h1>
                                <p>Article content here.</p>
                            </article>
                        </main>
                        <footer>Footer</footer>
                    </body>
                    </html>
                ', 200),
            ]);

            $document = $this->loader->load('https://example.com');

            expect($document->content())->toContain('Navigation');
            expect($document->content())->toContain('Article Title');
            expect($document->content())->toContain('Article content');
            expect($document->content())->toContain('Footer');
        });
    });

    describe('Protocol Handling', function () {
        it('accepts HTTPS URLs', function () {
            Http::fake(['*' => Http::response('<p>Content</p>', 200)]);

            $document = $this->loader->load('https://secure.example.com');

            expect($document)->toBeInstanceOf(Document::class);
        });

        it('accepts HTTP URLs', function () {
            Http::fake(['*' => Http::response('<p>Content</p>', 200)]);

            $document = $this->loader->load('http://example.com');

            expect($document)->toBeInstanceOf(Document::class);
        });
    });
});
