<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\Code;

use PHPUnit\Event\TestData\MoreThanOneDataSetFromDataProviderException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Logging\TestDox\NamePrettifier;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class TestDoxBuilder
{
    /**
     * @throws MoreThanOneDataSetFromDataProviderException
     */
    public static function fromTestCase(TestCase $testCase): TestDox
    {
        $prettifier = new NamePrettifier;

        return new TestDox(
            $prettifier->prettifyTestClassName($testCase::class),
            $prettifier->prettifyTestCase($testCase, false),
            $prettifier->prettifyTestCase($testCase, true),
        );
    }

    /**
     * @psalm-param class-string $className
     * @psalm-param non-empty-string $methodName
     */
    public static function fromClassNameAndMethodName(string $className, string $methodName): TestDox
    {
        $prettifier = new NamePrettifier;

        $prettifiedMethodName = $prettifier->prettifyTestMethodName($methodName);

        return new TestDox(
            $prettifier->prettifyTestClassName($className),
            $prettifiedMethodName,
            $prettifiedMethodName,
        );
    }
}
