<?php

namespace Mindwave\Mindwave\Support;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class TextUtils
{
    public static function normalizeWhitespace(string $text): string
    {
        return Str::of($text)->squish()->trim()->toString();
    }

    public static function cleanHtml(
        string $html,
        array $elementsToRemove = ['script', 'style', 'link', 'head', 'noscript', 'template', 'svg', 'br', 'hr'],
        bool $removeComments = true,
        bool $normalizeWhitespace = true
    ): string {
        $inputHtml = $normalizeWhitespace
            ? Str::of($html)
                ->replace('<', ' <')
                ->replace('>', '> ')
                ->toString()
            : $html;

        $crawler = new Crawler($inputHtml);

        // Remove elements we dont need
        foreach ($elementsToRemove as $element) {
            $crawler->filter($element)->each(function (Crawler $node) {
                $node->getNode(0)->parentNode->removeChild($node->getNode(0));
            });
        }

        // TODO(14 mai 2023) ~ Helge: Check if this is necessary
        if ($removeComments) {
            $crawler->filterXPath('//comment()')->each(function (Crawler $node) {
                $node->getNode(0)->parentNode->removeChild($node->getNode(0));
            });
        }

        return $normalizeWhitespace ? self::normalizeWhitespace($crawler->text('')) : $crawler->text('');
    }
}
