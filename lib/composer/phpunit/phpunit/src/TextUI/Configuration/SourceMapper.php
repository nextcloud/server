<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\Configuration;

use function realpath;
use SebastianBergmann\FileIterator\Facade as FileIteratorFacade;
use SplObjectStorage;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class SourceMapper
{
    /**
     * @psalm-var SplObjectStorage<Source, array<non-empty-string, true>>
     */
    private static ?SplObjectStorage $files = null;

    /**
     * @psalm-return array<non-empty-string, true>
     */
    public function map(Source $source): array
    {
        if (self::$files === null) {
            self::$files = new SplObjectStorage;
        }

        if (isset(self::$files[$source])) {
            return self::$files[$source];
        }

        $files = [];

        foreach ($source->includeDirectories() as $directory) {
            foreach ((new FileIteratorFacade)->getFilesAsArray($directory->path(), $directory->suffix(), $directory->prefix()) as $file) {
                $file = realpath($file);

                if (!$file) {
                    continue;
                }

                $files[$file] = true;
            }
        }

        foreach ($source->includeFiles() as $file) {
            $file = realpath($file->path());

            if (!$file) {
                continue;
            }

            $files[$file] = true;
        }

        foreach ($source->excludeDirectories() as $directory) {
            foreach ((new FileIteratorFacade)->getFilesAsArray($directory->path(), $directory->suffix(), $directory->prefix()) as $file) {
                $file = realpath($file);

                if (!$file) {
                    continue;
                }

                if (!isset($files[$file])) {
                    continue;
                }

                unset($files[$file]);
            }
        }

        foreach ($source->excludeFiles() as $file) {
            $file = realpath($file->path());

            if (!$file) {
                continue;
            }

            if (!isset($files[$file])) {
                continue;
            }

            unset($files[$file]);
        }

        self::$files[$source] = $files;

        return $files;
    }
}
