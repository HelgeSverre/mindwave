<?php

declare(strict_types=1);

use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Document\Loaders\TextLoader;

describe('TextLoader', function () {
    beforeEach(function () {
        $this->loader = new TextLoader();
    });

    it('loads plain text content', function () {
        $document = $this->loader->load('Hello, world!');

        expect($document)->toBeInstanceOf(Document::class);
        expect($document->content())->toBe('Hello, world!');
    });

    it('preserves content exactly as provided', function () {
        $content = "Line 1\nLine 2\n\nLine 4";

        $document = $this->loader->load($content);

        expect($document->content())->toBe($content);
    });

    it('loads empty string', function () {
        $document = $this->loader->load('');

        expect($document)->toBeInstanceOf(Document::class);
        expect($document->content())->toBe('');
    });

    it('includes provided metadata', function () {
        $meta = ['source' => 'user_input', 'timestamp' => time()];

        $document = $this->loader->load('Content', $meta);

        expect($document->metadata())->toBe($meta);
    });

    it('handles empty metadata', function () {
        $document = $this->loader->load('Content');

        expect($document->metadata())->toBe([]);
    });

    it('handles unicode content', function () {
        $content = '你好世界 👋 مرحبا';

        $document = $this->loader->load($content);

        expect($document->content())->toBe($content);
    });

    it('handles very long content', function () {
        $content = str_repeat('Word ', 10000);

        $document = $this->loader->load($content);

        expect($document->content())->toBe($content);
    });

    it('preserves whitespace', function () {
        $content = "   Leading spaces\n\tTabbed\n   Trailing spaces   ";

        $document = $this->loader->load($content);

        expect($document->content())->toBe($content);
    });

    it('handles special characters', function () {
        $content = '<script>alert("XSS")</script>';

        $document = $this->loader->load($content);

        expect($document->content())->toBe($content);
    });
});
