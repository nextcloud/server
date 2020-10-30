<?php
namespace Psalm;

use Webmozart\PathUtil\Path;

/**
 * @param string $path
 *
 * @deprecated Use {@see Webmozart\PathUtil\Path::isAbsolute} instead
 */
function isAbsolutePath($path): bool
{
    return Path::isAbsolute($path);
}
