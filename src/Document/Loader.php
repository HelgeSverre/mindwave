<?php

namespace Mindwave\Mindwave\Document;

use InvalidArgumentException;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Support\FileTypeDetector;

class Loader
{
    protected array $loaders = [];

    public function __construct(array $loaders = [])
    {
        $this->loaders = $loaders;
    }

    public function registerLoader(string $loaderName, Loader $loader): void
    {
        $this->loaders[$loaderName] = $loader;
    }

    public function loadDocument(string $loaderName, $input, ?array $meta = []): ?Document
    {
        if (! isset($this->loaders[$loaderName])) {
            throw new InvalidArgumentException("Loader $loaderName is not registered.");
        }

        return $this->loaders[$loaderName]->load($input, $meta);
    }

    public function fromPdf($data, ?array $meta = []): ?Document
    {
        return $this->loadDocument('pdf', $data, $meta);
    }

    public function fromHtml($data, ?array $meta = []): ?Document
    {
        return $this->loadDocument('html', $data, $meta);
    }

    public function fromUrl($data, ?array $meta = []): ?Document
    {
        return $this->loadDocument('url', $data, $meta);
    }

    public function fromWord($data, ?array $meta = []): ?Document
    {
        return $this->loadDocument('word', $data, $meta);
    }

    public function fromText($text, ?array $meta = []): ?Document
    {
        return Document::make($text, $meta);
    }

    /**
     * Attempt to load a document by detecting its content type.
     *
     * @internal This method is incomplete and only partially implemented.
     *
     * @deprecated Use specific loader methods like fromPdf(), fromHtml(), etc. instead.
     *             This method will be removed in a future version.
     */
    public function loadFromContent($content): ?Document
    {
        $type = FileTypeDetector::detectByContent($content);

        return match ($type) {
            'application/vnd.oasis.opendocument.text' => $this->fromWord($content),
            default => $this->fromText($content),
        };
    }
}
