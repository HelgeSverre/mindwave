<?php

namespace Mindwave\Mindwave\Knowledge;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Macroable;
use Mindwave\Mindwave\Support\TextUtils;
use Smalot\PdfParser\Parser;
use Stringable;

class Loader
{
    use Macroable;

    protected string $content;

    protected ?array $meta = [];

    public function __construct(string $content, ?array $meta)
    {
        $this->content = $content;
        $this->meta = $meta;
    }

    public static function fromPdf($pdf, ?array $meta = []): ?Knowledge
    {
        $pdfParser = new Parser();
        $document = $pdfParser->parseContent($pdf);

        $text = $document?->getText();

        // Replace control characters with space
        $text = preg_replace('/[[:cntrl:]]/', ' ', $text);

    }

    public static function fromUrl(string $url, ?array $meta = []): ?Knowledge
    {
        // TODO(14 mai 2023) ~ Helge: Configurable agent, timeout etc
        $response = Http::get($url);

        if ($response->failed()) {
            return null;
        }

        return self::fromHTML($response->body(), array_merge(['url' => $url], $meta));
    }

    public static function fromHTML($html, ?array $meta = []): ?Knowledge
    {
        return new Knowledge(
            content: TextUtils::cleanHtml($html),
            meta: $meta
        );
    }

    public static function fromText(Stringable $text, ?array $meta = []): ?Knowledge
    {
        return new Knowledge(
            content: $text,
            meta: $meta
        );
    }
}
