<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Metadata\Api;

use function array_unshift;
use function assert;
use function class_exists;
use PHPUnit\Metadata\Parser\Registry;
use PHPUnit\Util\Reflection;
use ReflectionClass;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class HookMethods
{
    /**
     * @psalm-var array<class-string, array{beforeClass: list<non-empty-string>, before: list<non-empty-string>, preCondition: list<non-empty-string>, postCondition: list<non-empty-string>, after: list<non-empty-string>, afterClass: list<non-empty-string>}>
     */
    private static array $hookMethods = [];

    /**
     * @psalm-param class-string $className
     *
     * @psalm-return array{beforeClass: list<non-empty-string>, before: list<non-empty-string>, preCondition: list<non-empty-string>, postCondition: list<non-empty-string>, after: list<non-empty-string>, afterClass: list<non-empty-string>}
     */
    public function hookMethods(string $className): array
    {
        if (!class_exists($className)) {
            return self::emptyHookMethodsArray();
        }

        if (isset(self::$hookMethods[$className])) {
            return self::$hookMethods[$className];
        }

        self::$hookMethods[$className] = self::emptyHookMethodsArray();

        foreach (Reflection::methodsInTestClass(new ReflectionClass($className)) as $method) {
            $methodName = $method->getName();

            assert(!empty($methodName));

            $metadata = Registry::parser()->forMethod($className, $methodName);

            if ($method->isStatic()) {
                if ($metadata->isBeforeClass()->isNotEmpty()) {
                    array_unshift(
                        self::$hookMethods[$className]['beforeClass'],
                        $methodName,
                    );
                }

                if ($metadata->isAfterClass()->isNotEmpty()) {
                    self::$hookMethods[$className]['afterClass'][] = $methodName;
                }
            }

            if ($metadata->isBefore()->isNotEmpty()) {
                array_unshift(
                    self::$hookMethods[$className]['before'],
                    $methodName,
                );
            }

            if ($metadata->isPreCondition()->isNotEmpty()) {
                array_unshift(
                    self::$hookMethods[$className]['preCondition'],
                    $methodName,
                );
            }

            if ($metadata->isPostCondition()->isNotEmpty()) {
                self::$hookMethods[$className]['postCondition'][] = $methodName;
            }

            if ($metadata->isAfter()->isNotEmpty()) {
                self::$hookMethods[$className]['after'][] = $methodName;
            }
        }

        return self::$hookMethods[$className];
    }

    /**
     * @psalm-return array{beforeClass: list<non-empty-string>, before: list<non-empty-string>, preCondition: list<non-empty-string>, postCondition: list<non-empty-string>, after: list<non-empty-string>, afterClass: list<non-empty-string>}
     */
    private function emptyHookMethodsArray(): array
    {
        return [
            'beforeClass'   => ['setUpBeforeClass'],
            'before'        => ['setUp'],
            'preCondition'  => ['assertPreConditions'],
            'postCondition' => ['assertPostConditions'],
            'after'         => ['tearDown'],
            'afterClass'    => ['tearDownAfterClass'],
        ];
    }
}
