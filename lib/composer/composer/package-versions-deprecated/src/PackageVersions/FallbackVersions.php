<?php

declare(strict_types=1);

namespace PackageVersions;

use Generator;
use OutOfBoundsException;
use UnexpectedValueException;
use function array_key_exists;
use function array_merge;
use function basename;
use function file_exists;
use function file_get_contents;
use function getcwd;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function sprintf;

/**
 * @internal
 *
 * This is a fallback for {@see \PackageVersions\Versions::getVersion()}
 * Do not use this class directly: it is intended to be only used when
 * {@see \PackageVersions\Versions} fails to be generated, which typically
 * happens when running composer with `--no-scripts` flag)
 */
final class FallbackVersions
{
    const ROOT_PACKAGE_NAME = 'unknown/root-package@UNKNOWN';

    private function __construct()
    {
    }

    /**
     * @throws OutOfBoundsException If a version cannot be located.
     * @throws UnexpectedValueException If the composer.lock file could not be located.
     */
    public static function getVersion(string $packageName): string
    {
        $versions = iterator_to_array(self::getVersions(self::getPackageData()));

        if (! array_key_exists($packageName, $versions)) {
            throw new OutOfBoundsException(
                'Required package "' . $packageName . '" is not installed: check your ./vendor/composer/installed.json and/or ./composer.lock files'
            );
        }

        return $versions[$packageName];
    }

    /**
     * @return mixed[]
     *
     * @throws UnexpectedValueException
     */
    private static function getPackageData(): array
    {
        $checkedPaths = [
            // The top-level project's ./vendor/composer/installed.json
            getcwd() . '/vendor/composer/installed.json',
            __DIR__ . '/../../../../composer/installed.json',
            // The top-level project's ./composer.lock
            getcwd() . '/composer.lock',
            __DIR__ . '/../../../../../composer.lock',
            // This package's composer.lock
            __DIR__ . '/../../composer.lock',
        ];

        $packageData = [];
        foreach ($checkedPaths as $path) {
            if (! file_exists($path)) {
                continue;
            }

            $data = json_decode(file_get_contents($path), true);
            switch (basename($path)) {
                case 'installed.json':
                    // composer 2.x installed.json format
                    if (isset($data['packages'])) {
                        $packageData[] = $data['packages'];
                    } else {
                        // composer 1.x installed.json format
                        $packageData[] = $data;
                    }

                    break;
                case 'composer.lock':
                    $packageData[] = $data['packages'] + ($data['packages-dev'] ?? []);
                    break;
                default:
                    // intentionally left blank
            }
        }

        if ($packageData !== []) {
            return array_merge(...$packageData);
        }

        throw new UnexpectedValueException(sprintf(
            'PackageVersions could not locate the `vendor/composer/installed.json` or your `composer.lock` '
            . 'location. This is assumed to be in %s. If you customized your composer vendor directory and ran composer '
            . 'installation with --no-scripts, or if you deployed without the required composer files, PackageVersions '
            . 'can\'t detect installed versions.',
            json_encode($checkedPaths)
        ));
    }

    /**
     * @param mixed[] $packageData
     *
     * @return Generator&string[]
     *
     * @psalm-return Generator<string, string>
     */
    private static function getVersions(array $packageData): Generator
    {
        foreach ($packageData as $package) {
            yield $package['name'] => $package['version'] . '@' . (
                $package['source']['reference'] ?? $package['dist']['reference'] ?? ''
            );
        }

        yield self::ROOT_PACKAGE_NAME => self::ROOT_PACKAGE_NAME;
    }
}
