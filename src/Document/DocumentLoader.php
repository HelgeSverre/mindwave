<?php

namespace Mindwave\Mindwave\Document;

use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Document\Loaders\HtmlLoader;
use Mindwave\Mindwave\Document\Loaders\PdfLoader;
use Mindwave\Mindwave\Document\Loaders\WebLoader;
use Smalot\PdfParser\Parser;

class DocumentLoader
{
    protected array $loaders = [];

    public function __construct(array $loaders = [])
    {
        $this->loaders = $loaders ?: $this->defaultLoaders();
    }

    protected function defaultLoaders(): array
    {
        return [
            'pdf' => new PdfLoader(new Parser()),
            'html' => new HtmlLoader(),
            'url' => new WebLoader(),
        ];
    }

    public function registerLoader(string $loaderName, DocumentLoader $loader): void
    {
        $this->loaders[$loaderName] = $loader;
    }

    public function load(string $loaderName, $input, ?array $meta = []): ?Document
    {
        if (isset($this->loaders[$loaderName])) {
            return $this->loaders[$loaderName]->load($input, $meta);
        }

        return null; // or throw an exception for unregistered loaders
    }

    public function fromPdf($pdf, ?array $meta = []): ?Document
    {
        return $this->load('pdf', $pdf, $meta);
    }

    public function fromHtml($html, ?array $meta = []): ?Document
    {
        return $this->load('html', $html, $meta);
    }

    public function fromUrl($url, ?array $meta = []): ?Document
    {
        return $this->load('url', $url, $meta);
    }

    public function fromText($text, ?array $meta = []): ?Document
    {
        return Document::make($text, $meta);
    }
}
