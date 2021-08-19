<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php81;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
final class Php81
{
    public static function array_is_list(array $array): bool
    {
        if ([] === $array) {
            return true;
        }

        $nextKey = -1;

        foreach ($array as $k => $v) {
            if ($k !== ++$nextKey) {
                return false;
            }
        }

        return true;
    }
}
