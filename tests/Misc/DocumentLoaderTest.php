<?php

use Illuminate\Support\Facades\Http;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\DocumentLoader;
use Mindwave\Mindwave\Support\FileTypeDetector;

it('loads content from a PDFs', function ($file) {
    $pdfContent = file_get_contents($file);

    expect($pdfContent)->not()->toBeNull();

    $knowledge = DocumentLoader::fromPdf($pdfContent);

    expect($knowledge)->toBeInstanceOf(Document::class)
        ->and($knowledge->content())->toContain('Lorem ipsum');
})->with([
    __DIR__.'/../data/samples/sample-1-page.pdf',
    __DIR__.'/../data/samples/sample-2-pages.pdf',
]);

it('loads content from a DOCX file', function ($file) {
    $content = file_get_contents($file);

    expect($content)->not()->toBeNull();

    $knowledge = DocumentLoader::fromWord($content);

    expect($knowledge)->toBeInstanceOf(Document::class)
        ->and($knowledge->content())->toContain('Sample Docx');
})->with([
    __DIR__.'/../data/samples/sample-1-page.docx',
    __DIR__.'/../data/samples/sample-2-pages.docx',
]);

it('loads content from a DOC file', function ($file) {
    $content = file_get_contents($file);

    expect($content)->not()->toBeNull();

    $knowledge = DocumentLoader::fromWord($content);

    expect($knowledge)->toBeInstanceOf(Document::class)
        ->and($knowledge->content())->toContain('This is a regular paragraph');
})->with([
    __DIR__.'/../data/samples/SampleDOCFile_200kb.doc',
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

it('can auto detect which content is in the file', function () {

    $dir = test_root('/data/samples');

    dump('FILE: '.$dir.'/file-sample_100kB.odt');
    expect(FileTypeDetector::detectByContent(file_get_contents($dir.'/file-sample_100kB.odt')))
        ->toEqual('application/vnd.oasis.opendocument.text');

    dump('FILE: '.$dir.'/file-sample_100kB.rtf');
    expect(FileTypeDetector::detectByContent(file_get_contents($dir.'/file-sample_100kB.rtf')))
        ->toEqual('application/rtf');

    dump('FILE: '.$dir.'/podcast.rss');
    expect(FileTypeDetector::detectByContent(file_get_contents($dir.'/podcast.rss')))
        ->toEqual('application/rss+xml');

    dump('FILE: '.$dir.'/sample.xml');
    expect(FileTypeDetector::detectByContent(file_get_contents($dir.'/sample.xml')))
        ->toEqual('application/xml');

    dump('FILE: '.$dir.'/sample-1-page.docx');
    expect(FileTypeDetector::detectByContent(file_get_contents($dir.'/sample-1-page.docx')))
        ->toEqual('application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    dump('FILE: '.$dir.'/sample-1-page.pdf');
    expect(FileTypeDetector::detectByContent(file_get_contents($dir.'/sample-1-page.pdf')))
        ->toEqual('application/pdf');

    dump('FILE: '.$dir.'/sample-2-pages.docx');
    expect(FileTypeDetector::detectByContent(file_get_contents($dir.'/sample-2-pages.docx')))
        ->toEqual('application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    dump('FILE: '.$dir.'/sample-2-pages.pdf');
    expect(FileTypeDetector::detectByContent(file_get_contents($dir.'/sample-2-pages.pdf')))
        ->toEqual('application/pdf');

    dump('FILE: '.$dir.'/samplepptx.pptx');
    expect(FileTypeDetector::detectByContent(file_get_contents($dir.'/samplepptx.pptx')))
        ->toEqual('application/vnd.openxmlformats-officedocument.presentationml.presentation');

    // TODO:  I suspect these test files are not actually excel files, but openoffice files pretending to be excel files...
    //    dump("FILE: " . $dir . '/file_example_XLS_1000.xls');
    //    expect(FileTypeDetector::detectByContent(file_get_contents($dir . '/file_example_XLS_1000.xls')))
    //        ->toEqual("application/vnd.ms-excel");
    //    dump("FILE: " . $dir . '/Financial Sample.xlsx');
    //    expect(FileTypeDetector::detectByContent(file_get_contents($dir . '/Financial Sample.xlsx')))
    //        ->toEqual("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
});
