<?php

namespace Mindwave\Mindwave\Document;

use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Document\Loaders\HtmlLoader;
use Mindwave\Mindwave\Document\Loaders\PdfLoader;
use Mindwave\Mindwave\Document\Loaders\WebLoader;
use Mindwave\Mindwave\Document\Loaders\WordLoader;
use Smalot\PdfParser\Parser;
use wapmorgan\FileTypeDetector\Detector;

class Loader
{
    protected array $loaders = [];

    public function __construct(array $loaders = [])
    {
        $this->loaders = $loaders ?: $this->defaultLoaders();
    }

    // TODO(20 mai 2023) ~ Helge: Register in service provider, use manager pattern instead
    protected function defaultLoaders(): array
    {
        return [
            'pdf' => new PdfLoader(new Parser()),
            'html' => new HtmlLoader(),
            'url' => new WebLoader(),
            'word' => new WordLoader(),
        ];
    }

    public function registerLoader(string $loaderName, Loader $loader): void
    {
        $this->loaders[$loaderName] = $loader;
    }

    public function loader(string $loaderName, $input, ?array $meta = []): ?Document
    {
        if (isset($this->loaders[$loaderName])) {
            return $this->loaders[$loaderName]->load($input, $meta);
        }

        return null; // or throw an exception for unregistered loaders
    }

    public function fromPdf($data, ?array $meta = []): ?Document
    {
        return $this->loader('pdf', $data, $meta);
    }

    public function fromHtml($data, ?array $meta = []): ?Document
    {
        return $this->loader('html', $data, $meta);
    }

    public function fromUrl($data, ?array $meta = []): ?Document
    {
        return $this->loader('url', $data, $meta);
    }

    public function fromWord($data, ?array $meta = []): ?Document
    {
        return $this->loader('word', $data, $meta);
    }

    public function load($data, ?array $meta = []): ?Document
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $data);
        rewind($stream);

        $type = Detector::detectByContent($stream);

        fclose($stream);

        dump($type);

        return null;
    }

    public function fromText($text, ?array $meta = []): ?Document
    {
        return Document::make($text, $meta);
    }
}
