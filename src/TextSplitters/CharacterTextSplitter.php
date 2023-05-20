<?php

namespace Mindwave\Mindwave\TextSplitters;


class CharacterTextSplitter extends TextSplitter
{
    protected string $separator;

    public function __construct(string $separator = "\n\n", int $chunkSize = 1000, int $chunkOverlap = 200)
    {
        parent::__construct($chunkSize, $chunkOverlap);

        $this->separator = $separator;
    }

    public function splitText(string $text): array
    {
        // First we naively split the large input into a bunch of smaller ones.
        $splits = $this->separator ? explode($this->separator, $text) : str_split($text);

        return $this->mergeSplits($splits, $this->separator);
    }
}
