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

class Comparator
{
    /**
     * Evaluates the expression: $version1 > $version2.
     *
     * @param string $version1
     * @param string $version2
     *
     * @return bool
     */
    public static function greaterThan($version1, $version2)
    {
        return self::compare($version1, '>', $version2);
    }

    /**
     * Evaluates the expression: $version1 >= $version2.
     *
     * @param string $version1
     * @param string $version2
     *
     * @return bool
     */
    public static function greaterThanOrEqualTo($version1, $version2)
    {
        return self::compare($version1, '>=', $version2);
    }

    /**
     * Evaluates the expression: $version1 < $version2.
     *
     * @param string $version1
     * @param string $version2
     *
     * @return bool
     */
    public static function lessThan($version1, $version2)
    {
        return self::compare($version1, '<', $version2);
    }

    /**
     * Evaluates the expression: $version1 <= $version2.
     *
     * @param string $version1
     * @param string $version2
     *
     * @return bool
     */
    public static function lessThanOrEqualTo($version1, $version2)
    {
        return self::compare($version1, '<=', $version2);
    }

    /**
     * Evaluates the expression: $version1 == $version2.
     *
     * @param string $version1
     * @param string $version2
     *
     * @return bool
     */
    public static function equalTo($version1, $version2)
    {
        return self::compare($version1, '==', $version2);
    }

    /**
     * Evaluates the expression: $version1 != $version2.
     *
     * @param string $version1
     * @param string $version2
     *
     * @return bool
     */
    public static function notEqualTo($version1, $version2)
    {
        return self::compare($version1, '!=', $version2);
    }

    /**
     * Evaluates the expression: $version1 $operator $version2.
     *
     * @param string $version1
     * @param string $operator
     * @param string $version2
     *
     * @return bool
     */
    public static function compare($version1, $operator, $version2)
    {
        $constraint = new Constraint($operator, $version2);

        return $constraint->matches(new Constraint('==', $version1));
    }
}
