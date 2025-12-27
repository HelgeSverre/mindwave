<?php

namespace Mindwave\Mindwave\Support;

use BrandEmbassy\FileTypeDetector\Detector;
use Illuminate\Support\Str;
use Throwable;

class FileTypeDetector
{
    public static function detectByContent($content): ?string
    {
        $stream = null;

        try {
            // Detector library works on resources, not "raw bytes"
            $stream = fopen('php://memory', 'r+');

            if ($stream === false) {
                return null;
            }

            fwrite($stream, $content);
            rewind($stream);

            $type = Detector::detectByContent($stream);

            // .odt files are in reality zip files, but we don't care about that here.
            if ($type?->getMimeType() == 'application/zip' &&
                Str::contains($content, 'application/vnd.oasis.opendocument.text')) {
                return 'application/vnd.oasis.opendocument.text';
            }

            return $type?->getMimeType();
        } catch (Throwable) {
            // File type detection is best-effort; return null for unknown types
            return null;
        } finally {
            if ($stream !== null && is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
