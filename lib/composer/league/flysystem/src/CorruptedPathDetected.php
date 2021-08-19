<?php

namespace League\Flysystem;

use LogicException;

class CorruptedPathDetected extends LogicException implements FilesystemException
{
    /**
     * @param string $path
     * @return CorruptedPathDetected
     */
    public static function forPath($path)
    {
        return new CorruptedPathDetected("Corrupted path detected: " . $path);
    }
}
