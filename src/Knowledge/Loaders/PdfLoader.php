<?php

namespace Mindwave\Mindwave\Knowledge\Loaders;

use Mindwave\Mindwave\Contracts\KnowledgeLoader;
use Mindwave\Mindwave\Knowledge\Data\Knowledge;
use Smalot\PdfParser\Parser;

class PdfLoader implements KnowledgeLoader
{
    protected Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function load(mixed $data, array $meta = []): ?Knowledge
    {
        return new Knowledge(
            content: $this->parser->parseContent($data)->getText(),
            meta: $meta,
        );
    }
}
