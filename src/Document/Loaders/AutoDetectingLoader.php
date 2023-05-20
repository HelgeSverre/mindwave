<?php

namespace Mindwave\Mindwave\Document\Loaders;

use Mindwave\Mindwave\Contracts\DocumentLoader;
use Mindwave\Mindwave\Document\Data\Document;
use wapmorgan\FileTypeDetector\Detector;

class AutoDetectingLoader implements DocumentLoader
{
    public function load(mixed $data, array $meta = []): ?Document
    {
        $type = Detector::detectByContent($data);

        // TODO: Implement load() method.
    }
}
