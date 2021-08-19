<?php

namespace League\Flysystem\Util;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;
use League\MimeTypeDetection\MimeTypeDetector;

/**
 * @internal
 */
class MimeType
{
    protected static $extensionToMimeTypeMap = GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS;
    protected static $detector;

    public static function useDetector(MimeTypeDetector $detector)
    {
        static::$detector = $detector;
    }

    /**
     * @return MimeTypeDetector
     */
    protected static function detector()
    {
        if ( ! static::$detector instanceof MimeTypeDetector) {
            static::$detector = new FinfoMimeTypeDetector();
        }

        return static::$detector;
    }


    /**
     * Detects MIME Type based on given content.
     *
     * @param mixed $content
     *
     * @return string MIME Type
     */
    public static function detectByContent($content)
    {
        if (is_string($content)) {
            return static::detector()->detectMimeTypeFromBuffer($content);
        }

        return 'text/plain';
    }

    /**
     * Detects MIME Type based on file extension.
     *
     * @param string $extension
     *
     * @return string MIME Type
     */
    public static function detectByFileExtension($extension)
    {
        return static::detector()->detectMimeTypeFromPath('artificial.' . $extension) ?: 'text/plain';
    }

    /**
     * @param string $filename
     *
     * @return string MIME Type
     */
    public static function detectByFilename($filename)
    {
        return static::detector()->detectMimeTypeFromPath($filename) ?: 'text/plain';
    }

    /**
     * @return array Map of file extension to MIME Type
     */
    public static function getExtensionToMimeTypeMap()
    {
        return static::$extensionToMimeTypeMap;
    }
}
