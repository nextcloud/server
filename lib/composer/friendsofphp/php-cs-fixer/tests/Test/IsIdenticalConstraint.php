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

namespace PhpCsFixer\Tests\Test;

use PhpCsFixer\PhpunitConstraintIsIdenticalString\Constraint\IsIdenticalString;
use PHPUnit\Framework\Constraint\IsIdentical as PhpUnitIsIdentical;

/**
 * @internal
 *
 * @todo Remove me when usages will end up in dedicated package.
 */
trait IsIdenticalConstraint
{
    /**
     * @todo Remove me when this class will end up in dedicated package.
     *
     * @param string $expected
     *
     * @return IsIdenticalString|\PHPUnit_Framework_Constraint_IsIdentical|PhpUnitIsIdentical
     */
    private static function createIsIdenticalStringConstraint($expected)
    {
        $candidate = self::getIsIdenticalStringConstraintClassName();

        return new $candidate($expected);
    }

    /**
     * @return string
     */
    private static function getIsIdenticalStringConstraintClassName()
    {
        foreach ([
            IsIdenticalString::class,
            PhpUnitIsIdentical::class,
            'PHPUnit_Framework_Constraint_IsIdentical',
        ] as $className) {
            if (class_exists($className)) {
                return $className;
            }
        }

        throw new \RuntimeException('PHPUnit not installed?!');
    }
}
