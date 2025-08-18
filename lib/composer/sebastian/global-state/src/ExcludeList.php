<?php declare(strict_types=1);
/*
 * This file is part of sebastian/global-state.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\GlobalState;

use function in_array;
use function str_starts_with;
use ReflectionClass;

final class ExcludeList
{
    private array $globalVariables   = [];
    private array $classes           = [];
    private array $classNamePrefixes = [];
    private array $parentClasses     = [];
    private array $interfaces        = [];
    private array $staticProperties  = [];

    public function addGlobalVariable(string $variableName): void
    {
        $this->globalVariables[$variableName] = true;
    }

    public function addClass(string $className): void
    {
        $this->classes[] = $className;
    }

    public function addSubclassesOf(string $className): void
    {
        $this->parentClasses[] = $className;
    }

    public function addImplementorsOf(string $interfaceName): void
    {
        $this->interfaces[] = $interfaceName;
    }

    public function addClassNamePrefix(string $classNamePrefix): void
    {
        $this->classNamePrefixes[] = $classNamePrefix;
    }

    public function addStaticProperty(string $className, string $propertyName): void
    {
        if (!isset($this->staticProperties[$className])) {
            $this->staticProperties[$className] = [];
        }

        $this->staticProperties[$className][$propertyName] = true;
    }

    public function isGlobalVariableExcluded(string $variableName): bool
    {
        return isset($this->globalVariables[$variableName]);
    }

    /**
     * @psalm-param class-string $className
     */
    public function isStaticPropertyExcluded(string $className, string $propertyName): bool
    {
        if (in_array($className, $this->classes, true)) {
            return true;
        }

        foreach ($this->classNamePrefixes as $prefix) {
            if (str_starts_with($className, $prefix)) {
                return true;
            }
        }

        $class = new ReflectionClass($className);

        foreach ($this->parentClasses as $type) {
            if ($class->isSubclassOf($type)) {
                return true;
            }
        }

        foreach ($this->interfaces as $type) {
            if ($class->implementsInterface($type)) {
                return true;
            }
        }

        return isset($this->staticProperties[$className][$propertyName]);
    }
}
