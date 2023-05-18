<?php

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Facades\KnowledgeLoader;
use Mindwave\Mindwave\Knowledge\Data\Document;

it('loads content from a PDFs', function ($file) {
    $pdfContent = file_get_contents($file);

    expect($pdfContent)->not()->toBeNull();

    $knowledge = KnowledgeLoader::fromPdf($pdfContent);

    expect($knowledge)->toBeInstanceOf(Document::class);
    expect($knowledge->content())->toContain('Lorem ipsum');
})->with([
    __DIR__.'/data/samples/sample-1-page.pdf',
    __DIR__.'/data/samples/sample-2-pages.pdf',
]);

// TODO(14 mai 2023) ~ Helge: Should null be returned, or an Error Object (functional optional/some pattern)?
it('If PDF is invalid, exception is thrown.', function () {
    expect(fn () => KnowledgeLoader::fromPdf('Not a valid PDF'))->toThrow(Exception::class);
});

it('loads content from a URL', function () {
    Http::fake([
        'https://example.com' => Http::response('<html><head><title>Ignored</title></head><body><h1>It works!</h1></body></html>'),
    ]);

    $knowledge = KnowledgeLoader::fromUrl('https://example.com');

    expect($knowledge)->toBeInstanceOf(Document::class);
    expect($knowledge->content())->toBe('It works!');
});

it('loads content from HTML', function () {
    $knowledge = KnowledgeLoader::fromHTML('<html><head><title>Ignored</title></head><body><h1>It works!</h1></body></html>');

    expect($knowledge)->toBeInstanceOf(Document::class);
    expect($knowledge->content())->toBe('It works!');
});

it('loads content from text', function () {
    $textContent = 'Hello, i am a text document'; // Provide a sample text content

    $knowledge = KnowledgeLoader::fromText($textContent);

    expect($knowledge)->toBeInstanceOf(Document::class);
    expect($knowledge->content())->toBe($textContent);
});
