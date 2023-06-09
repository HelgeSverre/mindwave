<?php

namespace Mindwave\Mindwave\Document\Loaders;

use Mindwave\Mindwave\Contracts\DocumentLoader;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Support\TextUtils;
use Smalot\PdfParser\Parser;

class PdfLoader implements DocumentLoader
{
    protected Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function load(mixed $data, array $meta = []): ?Document
    {
        return new Document(
            content: TextUtils::normalizeWhitespace(
                $this->parser->parseContent($data)->getText()
            ),
            metadata: $meta,
        );
    }
}
