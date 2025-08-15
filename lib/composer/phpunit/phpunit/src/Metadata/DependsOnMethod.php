<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Metadata;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class DependsOnMethod extends Metadata
{
    /**
     * @psalm-var class-string
     */
    private readonly string $className;

    /**
     * @psalm-var non-empty-string
     */
    private readonly string $methodName;
    private readonly bool $deepClone;
    private readonly bool $shallowClone;

    /**
     * @psalm-param 0|1 $level
     * @psalm-param class-string $className
     * @psalm-param non-empty-string $methodName
     */
    protected function __construct(int $level, string $className, string $methodName, bool $deepClone, bool $shallowClone)
    {
        parent::__construct($level);

        $this->className    = $className;
        $this->methodName   = $methodName;
        $this->deepClone    = $deepClone;
        $this->shallowClone = $shallowClone;
    }

    /**
     * @psalm-assert-if-true DependsOnMethod $this
     */
    public function isDependsOnMethod(): bool
    {
        return true;
    }

    /**
     * @psalm-return class-string
     */
    public function className(): string
    {
        return $this->className;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function methodName(): string
    {
        return $this->methodName;
    }

    public function deepClone(): bool
    {
        return $this->deepClone;
    }

    public function shallowClone(): bool
    {
        return $this->shallowClone;
    }
}
