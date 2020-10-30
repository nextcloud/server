<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Semver;

use Composer\Semver\Constraint\Constraint;

class Semver
{
    const SORT_ASC = 1;
    const SORT_DESC = -1;

    /** @var VersionParser */
    private static $versionParser;

    /**
     * Determine if given version satisfies given constraints.
     *
     * @param string $version
     * @param string $constraints
     *
     * @return bool
     */
    public static function satisfies($version, $constraints)
    {
        if (null === self::$versionParser) {
            self::$versionParser = new VersionParser();
        }

        $versionParser = self::$versionParser;
        $provider = new Constraint('==', $versionParser->normalize($version));
        $parsedConstraints = $versionParser->parseConstraints($constraints);

        return $parsedConstraints->matches($provider);
    }

    /**
     * Return all versions that satisfy given constraints.
     *
     * @param array  $versions
     * @param string $constraints
     *
     * @return array
     */
    public static function satisfiedBy(array $versions, $constraints)
    {
        $versions = array_filter($versions, function ($version) use ($constraints) {
            return Semver::satisfies($version, $constraints);
        });

        return array_values($versions);
    }

    /**
     * Sort given array of versions.
     *
     * @param array $versions
     *
     * @return array
     */
    public static function sort(array $versions)
    {
        return self::usort($versions, self::SORT_ASC);
    }

    /**
     * Sort given array of versions in reverse.
     *
     * @param array $versions
     *
     * @return array
     */
    public static function rsort(array $versions)
    {
        return self::usort($versions, self::SORT_DESC);
    }

    /**
     * @param array $versions
     * @param int   $direction
     *
     * @return array
     */
    private static function usort(array $versions, $direction)
    {
        if (null === self::$versionParser) {
            self::$versionParser = new VersionParser();
        }

        $versionParser = self::$versionParser;
        $normalized = array();

        // Normalize outside of usort() scope for minor performance increase.
        // Creates an array of arrays: [[normalized, key], ...]
        foreach ($versions as $key => $version) {
            $normalized[] = array($versionParser->normalize($version), $key);
        }

        usort($normalized, function (array $left, array $right) use ($direction) {
            if ($left[0] === $right[0]) {
                return 0;
            }

            if (Comparator::lessThan($left[0], $right[0])) {
                return -$direction;
            }

            return $direction;
        });

        // Recreate input array, using the original indexes which are now in sorted order.
        $sorted = array();
        foreach ($normalized as $item) {
            $sorted[] = $versions[$item[1]];
        }

        return $sorted;
    }
}
