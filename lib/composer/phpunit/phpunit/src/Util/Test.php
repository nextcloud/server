<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Util;

use function str_starts_with;
use PHPUnit\Metadata\Parser\Registry;
use ReflectionMethod;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class Test
{
    public static function isTestMethod(ReflectionMethod $method): bool
    {
        if (!$method->isPublic()) {
            return false;
        }

        if (str_starts_with($method->getName(), 'test')) {
            return true;
        }

        $metadata = Registry::parser()->forMethod(
            $method->getDeclaringClass()->getName(),
            $method->getName(),
        );

        return $metadata->isTest()->isNotEmpty();
    }
}
