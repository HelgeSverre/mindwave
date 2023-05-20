<?php


use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\DocumentLoader;
use Mindwave\Mindwave\TextSplitters\CharacterTextSplitter;
use Mindwave\Mindwave\TextSplitters\RecursiveCharacterTextSplitter;

it('splits the text into chunks')
    ->expect(function () {
        $text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed accumsan tortor id ex tincidunt condimentum. Nulla bibendum urna nec laoreet euismod. Suspendisse tempor ex eget nibh gravida interdum. Duis convallis urna at diam mattis, a dictum massa egestas. Nam ac orci vitae justo vulputate sagittis. Fusce consectetur rutrum arcu in convallis. Aliquam venenatis libero nec sem ultricies placerat. Pellentesque vel metus non ligula finibus finibus. Suspendisse id nisl id tellus dapibus egestas.";

        $splitter = new CharacterTextSplitter();
        $chunks = $splitter->splitText($text);

        return count($chunks);
    })
    ->toBeGreaterThan(0);


it('splits pdf files into chunks')
    ->expect(function () {
        $text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed accumsan tortor id ex tincidunt condimentum. Nulla bibendum urna nec laoreet euismod. Suspendisse tempor ex eget nibh gravida interdum. Duis convallis urna at diam mattis, a dictum massa egestas. Nam ac orci vitae justo vulputate sagittis. Fusce consectetur rutrum arcu in convallis. Aliquam venenatis libero nec sem ultricies placerat. Pellentesque vel metus non ligula finibus finibus. Suspendisse id nisl id tellus dapibus egestas.";

        $splitter = new CharacterTextSplitter();
        $chunks = $splitter->splitText($text);

        return count($chunks);
    })
    ->toBeGreaterThan(0);

it('loads content from a PDFs', function ($file) {
    $pdfContent = file_get_contents($file);

    expect($pdfContent)->not()->toBeNull();

    $document = DocumentLoader::fromPdf($pdfContent, [
        "id" => "test",
        "source" => $file
    ]);


    $splitter = new RecursiveCharacterTextSplitter(["\t", "\n"], chunkSize: 180, chunkOverlap: 80);

    $chunks = $splitter->splitDocument($document);

    dump(count($chunks));
    expect($document)->toBeInstanceOf(Document::class);
    expect($document->content())->toContain('Lorem ipsum');
})->with([
    __DIR__ . '/data/samples/sample-1-page.pdf',
    __DIR__ . '/data/samples/sample-2-pages.pdf',
]);


it('splits the text using a custom separator')
    ->expect(function () {
        $text = "Lorem ipsum\ndolor sit amet\nconsectetur adipiscing\nelit";

        $splitter = new CharacterTextSplitter('\n');
        $chunks = $splitter->splitText($text);

        return count($chunks);
    })
    ->toBeGreaterThan(0);

it('creates documents from texts')
    ->expect(function () {
        $texts = [
            "Lorem ipsum dolor",
            "sit amet, consectetur",
            "adipiscing elit"
        ];

        $splitter = new CharacterTextSplitter();
        $documents = $splitter->createDocuments($texts);

        return count($documents);
    })
    ->toBeGreaterThan(0);

it('splits documents')
    ->expect(function () {
        $documents = [
            new Document('Lorem ipsum dolor'),
            new Document('sit amet, consectetur'),
            new Document('adipiscing elit')
        ];

        $splitter = new CharacterTextSplitter();
        $splitDocuments = $splitter->splitDocuments($documents);

        return count($splitDocuments);
    })
    ->toBeGreaterThan(0);


