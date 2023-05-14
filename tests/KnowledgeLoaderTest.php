<?php

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Knowledge\Knowledge;
use Mindwave\Mindwave\Knowledge\Loader;

it('can load PDF files', function ($file) {
    $pdfContent = file_get_contents($file);

    expect($pdfContent)->not()->toBeNull();

    $knowledge = Loader::fromPdf($pdfContent);

    expect($knowledge)->toBeInstanceOf(Knowledge::class);
    expect($knowledge->content())->toContain('Lorem ipsum');
})->with([
    __DIR__.'/data/samples/sample-1-page.pdf',
    __DIR__.'/data/samples/sample-2-pages.pdf',
]);

// TODO(14 mai 2023) ~ Helge: Should null be returned, or an Error Object (functional optional/some pattern)?
it('If PDF is invalid, exception is thrown.', function () {
    expect(fn () => Loader::fromPdf('Not a valid PDF'))->toThrow(Exception::class);
});

it('loads content from a URL', function () {
    Http::fake([
        'https://example.com' => Http::response('<html><head><title>Ignored</title></head><body><h1>It works!</h1></body></html>'),
    ]);

    $knowledge = Loader::fromUrl('https://example.com');

    expect($knowledge)->toBeInstanceOf(Knowledge::class);
    expect($knowledge->content())->toBe('It works!');
});

it('loads content from HTML', function () {
    $knowledge = Loader::fromHTML('<html><head><title>Ignored</title></head><body><h1>It works!</h1></body></html>');

    expect($knowledge)->toBeInstanceOf(Knowledge::class);
    expect($knowledge->content())->toBe('It works!');
});

it('loads content from text', function () {
    $textContent = 'Hello, i am a text document'; // Provide a sample text content

    $knowledge = Loader::fromText($textContent);

    expect($knowledge)->toBeInstanceOf(Knowledge::class);
    expect($knowledge->content())->toBe($textContent);
});
