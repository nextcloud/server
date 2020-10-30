<?php

namespace Psalm\Internal;

use function basename;
use function getenv;
use function pathinfo;
use function substr;
use function trim;
use const PATHINFO_EXTENSION;

/**
 * @internal
 */
final class Composer
{
    /**
     * Retrieve the path to composer.json file.
     *
     * @see https://github.com/composer/composer/blob/5df1797d20c6ab1eb606dc0f0d76a16ba57ddb7f/src/Composer/Factory.php#L233
     */
    public static function getJsonFilePath(string $root): string
    {
        $file_name = getenv('COMPOSER') ?: 'composer.json';
        $file_name = basename(trim($file_name));

        return $root . '/' . $file_name;
    }

    /**
     * Retrieve the path to composer.lock file.
     *
     * @see https://github.com/composer/composer/blob/5df1797d20c6ab1eb606dc0f0d76a16ba57ddb7f/src/Composer/Factory.php#L238
     */
    public static function getLockFilePath(string $root): string
    {
        $composer_json_path = static::getJsonFilePath($root);
        return "json" === pathinfo($composer_json_path, PATHINFO_EXTENSION)
            ? substr($composer_json_path, 0, -4).'lock'
            : $composer_json_path . '.lock';
    }
}
