<?php

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\DocumentLoader;

it('loads content from a PDFs', function ($file) {
    $pdfContent = file_get_contents($file);

    expect($pdfContent)->not()->toBeNull();

    $knowledge = DocumentLoader::fromPdf($pdfContent);

    expect($knowledge)->toBeInstanceOf(Document::class);
    expect($knowledge->content())->toContain('Lorem ipsum');
})->with([
    __DIR__ . '/data/samples/sample-1-page.pdf',
    __DIR__ . '/data/samples/sample-2-pages.pdf',
]);

// TODO(14 mai 2023) ~ Helge: Should null be returned, or an Error Object (functional optional/some pattern)?
it('If PDF is invalid, exception is thrown.', function () {
    expect(fn() => DocumentLoader::fromPdf('Not a valid PDF'))->toThrow(Exception::class);
});

it('loads content from a URL', function () {
    Http::fake([
        'https://example.com' => Http::response('<html><head><title>Ignored</title></head><body><h1>It works!</h1></body></html>'),
    ]);

    $knowledge = DocumentLoader::fromUrl('https://example.com');

    expect($knowledge)->toBeInstanceOf(Document::class);
    expect($knowledge->content())->toBe('It works!');
});

it('loads content from HTML', function () {
    $knowledge = DocumentLoader::fromHTML('<html><head><title>Ignored</title></head><body><h1>It works!</h1></body></html>');

    expect($knowledge)->toBeInstanceOf(Document::class);
    expect($knowledge->content())->toBe('It works!');
});

it('loads content from text', function () {
    $textContent = 'Hello, i am a text document'; // Provide a sample text content

    $knowledge = DocumentLoader::fromText($textContent);

    expect($knowledge)->toBeInstanceOf(Document::class);
    expect($knowledge->content())->toBe($textContent);
});


it('can auto detect which content is in the file', function ($file) {

    dump($file);

    $document = DocumentLoader::load(file_get_contents($file));
    // TODO(20 mai 2023) ~ Helge: finish this test


//    expect($document)->toBeInstanceOf(Document::class, "Failed to parse: {$file}");
//    expect($document->content())->toBeString("Failed to get text from {$file}");
})->with([
    __DIR__ . '/data/samples/flags-royal-palace-norway-en.txt',
    __DIR__ . "/data/samples/file-sample_100kB.odt",
    __DIR__ . "/data/samples/file-sample_100kB.rtf",
    __DIR__ . "/data/samples/file_example_XLS_1000.xls",
    __DIR__ . "/data/samples/Financial Sample.xlsx",
    __DIR__ . "/data/samples/podcast.rss",
    __DIR__ . "/data/samples/sample.xml",
    __DIR__ . "/data/samples/sample-1-page.docx",
    __DIR__ . "/data/samples/sample-1-page.pdf",
    __DIR__ . "/data/samples/sample-2-pages.docx",
    __DIR__ . "/data/samples/sample-2-pages.pdf",
    __DIR__ . "/data/samples/samplepptx.pptx",
]);
