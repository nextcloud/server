<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Util;

use function is_dir;
use function mkdir;
use function sprintf;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Filesystem
{
    /**
     * @throws DirectoryCouldNotBeCreatedException
     */
    public static function createDirectory(string $directory): void
    {
        $success = !(!is_dir($directory) && !@mkdir($directory, 0o777, true) && !is_dir($directory));

        if (!$success) {
            throw new DirectoryCouldNotBeCreatedException(
                sprintf(
                    'Directory "%s" could not be created',
                    $directory,
                ),
            );
        }
    }
}
