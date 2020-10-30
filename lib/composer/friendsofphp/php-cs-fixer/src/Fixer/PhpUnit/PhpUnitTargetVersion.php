<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\PhpUnit;

use Composer\Semver\Comparator;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class PhpUnitTargetVersion
{
    const VERSION_3_0 = '3.0';
    const VERSION_3_2 = '3.2';
    const VERSION_3_5 = '3.5';
    const VERSION_4_3 = '4.3';
    const VERSION_4_8 = '4.8';
    const VERSION_5_0 = '5.0';
    const VERSION_5_2 = '5.2';
    const VERSION_5_4 = '5.4';
    const VERSION_5_5 = '5.5';
    const VERSION_5_6 = '5.6';
    const VERSION_5_7 = '5.7';
    const VERSION_6_0 = '6.0';
    const VERSION_7_5 = '7.5';
    const VERSION_NEWEST = 'newest';

    private function __construct()
    {
    }

    /**
     * @param string $candidate
     * @param string $target
     *
     * @return bool
     */
    public static function fulfills($candidate, $target)
    {
        if (self::VERSION_NEWEST === $target) {
            throw new \LogicException(sprintf('Parameter `target` shall not be provided as `%s`, determine proper target for tested PHPUnit feature instead.', self::VERSION_NEWEST));
        }

        if (self::VERSION_NEWEST === $candidate) {
            return true;
        }

        return Comparator::greaterThanOrEqualTo($candidate, $target);
    }
}
