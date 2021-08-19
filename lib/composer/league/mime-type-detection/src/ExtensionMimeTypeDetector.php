<?php

declare(strict_types=1);

namespace League\MimeTypeDetection;

use const PATHINFO_EXTENSION;

class ExtensionMimeTypeDetector implements MimeTypeDetector
{
    /**
     * @var ExtensionToMimeTypeMap
     */
    private $extensions;

    public function __construct(ExtensionToMimeTypeMap $extensions = null)
    {
        $this->extensions = $extensions ?: new GeneratedExtensionToMimeTypeMap();
    }

    public function detectMimeType(string $path, $contents): ?string
    {
        return $this->detectMimeTypeFromPath($path);
    }

    public function detectMimeTypeFromPath(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $this->extensions->lookupMimeType($extension);
    }

    public function detectMimeTypeFromFile(string $path): ?string
    {
        return $this->detectMimeTypeFromPath($path);
    }

    public function detectMimeTypeFromBuffer(string $contents): ?string
    {
        return null;
    }
}
