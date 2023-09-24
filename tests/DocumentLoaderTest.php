<?php

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\DocumentLoader;

it('loads content from a PDFs', function ($file) {
    $pdfContent = file_get_contents($file);

    expect($pdfContent)->not()->toBeNull();

    $knowledge = DocumentLoader::fromPdf($pdfContent);

    expect($knowledge)->toBeInstanceOf(Document::class)
        ->and($knowledge->content())->toContain('Lorem ipsum');
})->with([
    __DIR__.'/data/samples/sample-1-page.pdf',
    __DIR__.'/data/samples/sample-2-pages.pdf',
]);

it('loads content from a DOCX file', function ($file) {
    $content = file_get_contents($file);

    expect($content)->not()->toBeNull();

    $knowledge = DocumentLoader::fromWord($content);

    expect($knowledge)->toBeInstanceOf(Document::class)
        ->and($knowledge->content())->toContain('Sample Docx');
})->with([
    __DIR__.'/data/samples/sample-1-page.docx',
    __DIR__.'/data/samples/sample-2-pages.docx',
]);

it('loads content from a DOC file', function ($file) {
    $content = file_get_contents($file);

    expect($content)->not()->toBeNull();

    $knowledge = DocumentLoader::fromWord($content);

    expect($knowledge)->toBeInstanceOf(Document::class)
        ->and($knowledge->content())->toContain('This is a regular paragraph');
})->with([
    __DIR__.'/data/samples/SampleDOCFile_200kb.doc',
]);

// TODO(14 mai 2023) ~ Helge: Should null be returned, or an Error Object (functional optional/some pattern)?
it('If PDF is invalid, exception is thrown.', function () {
    expect(fn () => DocumentLoader::fromPdf('Not a valid PDF'))->toThrow(Exception::class);
});

it('loads content from a URL', function () {
    Http::fake([
        'https://example.com' => Http::response('<html><head><title>Ignored</title></head><body><h1>It works!</h1></body></html>'),
    ]);

    $knowledge = DocumentLoader::fromUrl('https://example.com');

    expect($knowledge)->toBeInstanceOf(Document::class)
        ->and($knowledge->content())->toBe('It works!');
});

it('loads content from HTML', function () {
    $knowledge = DocumentLoader::fromHTML('<html><head><title>Ignored</title></head><body><h1>It works!</h1></body></html>');

    expect($knowledge)->toBeInstanceOf(Document::class)
        ->and($knowledge->content())->toBe('It works!');
});

it('loads content from text', function () {
    $textContent = 'Hello, i am a text document'; // Provide a sample text content

    $knowledge = DocumentLoader::fromText($textContent);

    expect($knowledge)->toBeInstanceOf(Document::class)
        ->and($knowledge->content())->toBe($textContent);
});

it('can auto detect which content is in the file', function ($file) {
    $document = DocumentLoader::loadFromContent(file_get_contents($file));

    expect($document)->toBeInstanceOf(Document::class, "Failed to parse: {$file}")
        ->and($document->content())->toBeString();
})->with([
    __DIR__.'/data/samples/flags-royal-palace-norway-en.txt',
    __DIR__.'/data/samples/file-sample_100kB.odt',
    __DIR__.'/data/samples/file-sample_100kB.rtf',
    __DIR__.'/data/samples/file_example_XLS_1000.xls',
    __DIR__.'/data/samples/Financial Sample.xlsx',
    __DIR__.'/data/samples/podcast.rss',
    __DIR__.'/data/samples/sample.xml',
    __DIR__.'/data/samples/sample-1-page.docx',
    __DIR__.'/data/samples/sample-1-page.pdf',
    __DIR__.'/data/samples/sample-2-pages.docx',
    __DIR__.'/data/samples/sample-2-pages.pdf',
    __DIR__.'/data/samples/samplepptx.pptx',
]);
