<?php

namespace Mindwave\Mindwave\TextSplitters;


class RecursiveCharacterTextSplitter extends TextSplitter
{
    protected array $separators;


    public function __construct(
        array $separators = ["\n\n", "\n", ' ', ''],
        int   $chunkSize = 1000,
        int   $chunkOverlap = 200
    )
    {
        parent::__construct($chunkSize, $chunkOverlap);

        $this->separators = $separators;
    }


    public function splitText(string $text): array
    {
        $finalChunks = [];

        // Get the appropriate separator to use
        $selectedSeparator = end($this->separators);
        foreach ($this->separators as $separator) {
            // If an empty separator is found, use it and exit the loop
            if ($separator == '') {
                $selectedSeparator = $separator;
                break;
            }

            // If the text contains the current separator, use it and exit the loop
            if (str_contains($text, $separator)) {
                $selectedSeparator = $separator;
                break;
            }
        }

        // Now that we have the separator, split the text
        // If no separator is found, split the text into individual characters
        $splits = $selectedSeparator ? explode($selectedSeparator, $text) : str_split($text);

        // Process the splits, recursively splitting longer texts
        $goodSplits = [];
        foreach ($splits as $split) {
            if (strlen($split) < $this->chunkSize) {
                // If the split is within the desired chunk size, add it to the good splits
                $goodSplits[] = $split;
            } else {
                if ($goodSplits) {
                    // Merge the good splits and add them to the final chunks
                    $finalChunks = array_merge($finalChunks, $this->mergeSplits($goodSplits, $selectedSeparator));
                    $goodSplits = [];
                }

                // Recursively split the longer text and add the resulting chunks to the final chunks
                $finalChunks = array_merge($finalChunks, $this->splitText($split));
            }
        }

        // Merge any remaining good splits and add them to the final chunks
        if ($goodSplits) {
            $finalChunks = array_merge($finalChunks, $this->mergeSplits($goodSplits, $selectedSeparator));
        }

        return $finalChunks;
    }

}
