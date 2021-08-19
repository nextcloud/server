<?php

declare(strict_types=1);

namespace League\MimeTypeDetection;

use const FILEINFO_MIME_TYPE;

use const PATHINFO_EXTENSION;
use finfo;

class FinfoMimeTypeDetector implements MimeTypeDetector
{
    private const INCONCLUSIVE_MIME_TYPES = ['application/x-empty', 'text/plain', 'text/x-asm'];

    /**
     * @var finfo
     */
    private $finfo;

    /**
     * @var ExtensionToMimeTypeMap
     */
    private $extensionMap;

    /**
     * @var int|null
     */
    private $bufferSampleSize;

    public function __construct(
        string $magicFile = '',
        ExtensionToMimeTypeMap $extensionMap = null,
        ?int $bufferSampleSize = null
    ) {
        $this->finfo = new finfo(FILEINFO_MIME_TYPE, $magicFile);
        $this->extensionMap = $extensionMap ?: new GeneratedExtensionToMimeTypeMap();
        $this->bufferSampleSize = $bufferSampleSize;
    }

    public function detectMimeType(string $path, $contents): ?string
    {
        $mimeType = is_string($contents)
            ? (@$this->finfo->buffer($this->takeSample($contents)) ?: null)
            : null;

        if ($mimeType !== null && ! in_array($mimeType, self::INCONCLUSIVE_MIME_TYPES)) {
            return $mimeType;
        }

        return $this->detectMimeTypeFromPath($path);
    }

    public function detectMimeTypeFromPath(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $this->extensionMap->lookupMimeType($extension);
    }

    public function detectMimeTypeFromFile(string $path): ?string
    {
        return @$this->finfo->file($path) ?: null;
    }

    public function detectMimeTypeFromBuffer(string $contents): ?string
    {
        return @$this->finfo->buffer($this->takeSample($contents)) ?: null;
    }

    private function takeSample(string $contents): string
    {
        if ($this->bufferSampleSize === null) {
            return $contents;
        }

        return (string) substr($contents, 0, $this->bufferSampleSize);
    }
}
