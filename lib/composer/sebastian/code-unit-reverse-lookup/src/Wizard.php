<?php declare(strict_types=1);
/*
 * This file is part of sebastian/code-unit-reverse-lookup.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeUnitReverseLookup;

use function array_merge;
use function assert;
use function get_declared_classes;
use function get_declared_traits;
use function get_defined_functions;
use function is_array;
use function range;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class Wizard
{
    /**
     * @psalm-var array<string,array<int,string>>
     */
    private array $lookupTable = [];

    /**
     * @psalm-var array<class-string,true>
     */
    private array $processedClasses = [];

    /**
     * @psalm-var array<string,true>
     */
    private array $processedFunctions = [];

    public function lookup(string $filename, int $lineNumber): string
    {
        if (!isset($this->lookupTable[$filename][$lineNumber])) {
            $this->updateLookupTable();
        }

        if (isset($this->lookupTable[$filename][$lineNumber])) {
            return $this->lookupTable[$filename][$lineNumber];
        }

        return $filename . ':' . $lineNumber;
    }

    private function updateLookupTable(): void
    {
        $this->processClassesAndTraits();
        $this->processFunctions();
    }

    private function processClassesAndTraits(): void
    {
        $classes = get_declared_classes();
        $traits  = get_declared_traits();

        /* @noinspection PhpConditionAlreadyCheckedInspection */
        assert(is_array($traits));

        foreach (array_merge($classes, $traits) as $classOrTrait) {
            if (isset($this->processedClasses[$classOrTrait])) {
                continue;
            }

            foreach ((new ReflectionClass($classOrTrait))->getMethods() as $method) {
                $this->processFunctionOrMethod($method);
            }

            $this->processedClasses[$classOrTrait] = true;
        }
    }

    private function processFunctions(): void
    {
        foreach (get_defined_functions()['user'] as $function) {
            if (isset($this->processedFunctions[$function])) {
                continue;
            }

            $this->processFunctionOrMethod(new ReflectionFunction($function));

            $this->processedFunctions[$function] = true;
        }
    }

    private function processFunctionOrMethod(ReflectionFunctionAbstract $functionOrMethod): void
    {
        if ($functionOrMethod->isInternal()) {
            return;
        }

        $name = $functionOrMethod->getName();

        if ($functionOrMethod instanceof ReflectionMethod) {
            $name = $functionOrMethod->getDeclaringClass()->getName() . '::' . $name;
        }

        if (!isset($this->lookupTable[$functionOrMethod->getFileName()])) {
            $this->lookupTable[$functionOrMethod->getFileName()] = [];
        }

        foreach (range($functionOrMethod->getStartLine(), $functionOrMethod->getEndLine()) as $line) {
            $this->lookupTable[$functionOrMethod->getFileName()][$line] = $name;
        }
    }
}
