<?php

namespace Mindwave\Mindwave\Knowledge;

use Mindwave\Mindwave\Knowledge\Data\Knowledge;
use Mindwave\Mindwave\Knowledge\Loaders\HtmlLoader;
use Mindwave\Mindwave\Knowledge\Loaders\PdfLoader;
use Mindwave\Mindwave\Knowledge\Loaders\UrlLoader;
use Smalot\PdfParser\Parser;

class KnowledgeLoader
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
            'url' => new UrlLoader(),
        ];
    }

    public function registerLoader(string $loaderName, KnowledgeLoader $loader): void
    {
        $this->loaders[$loaderName] = $loader;
    }

    public function load(string $loaderName, $input, ?array $meta = []): ?Knowledge
    {
        if (isset($this->loaders[$loaderName])) {
            return $this->loaders[$loaderName]->load($input, $meta);
        }

        return null; // or throw an exception for unregistered loaders
    }

    public function fromPdf($pdf, ?array $meta = []): ?Knowledge
    {
        return $this->load('pdf', $pdf, $meta);
    }

    public function fromHtml($html, ?array $meta = []): ?Knowledge
    {
        return $this->load('html', $html, $meta);
    }

    public function fromUrl($url, ?array $meta = []): ?Knowledge
    {
        return $this->load('url', $url, $meta);
    }

    public function fromText($text, ?array $meta = []): ?Knowledge
    {
        return Knowledge::make($text, $meta);
    }
}
