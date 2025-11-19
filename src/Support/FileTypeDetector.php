<?php

namespace Mindwave\Mindwave\Support;

use BrandEmbassy\FileTypeDetector\Detector;
use Illuminate\Support\Str;
use Throwable;

class FileTypeDetector
{
    public static function detectByContent($content): ?string
    {
        try {
            // Detector library works on resources, not "raw bytes"
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $content);
            rewind($stream);

            $type = Detector::detectByContent($stream);

            // .odt files are in reality zip files, but we don't care about that here.
            if ($type?->getMimeType() == 'application/zip' &&
                Str::contains($content, 'application/vnd.oasis.opendocument.text')) {
                return 'application/vnd.oasis.opendocument.text';
            }

            return $type?->getMimeType();
        } catch (Throwable $exception) {
            // TODO: throw exception ?
            return null;
        } finally {
            // Close stream so we don't leak memory
            fclose($stream);
        }
    }
}
