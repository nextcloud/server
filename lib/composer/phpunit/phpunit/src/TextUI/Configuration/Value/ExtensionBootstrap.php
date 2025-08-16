<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\Configuration;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @psalm-immutable
 */
final class ExtensionBootstrap
{
    /**
     * @psalm-var class-string
     */
    private readonly string $className;

    /**
     * @psalm-var array<string,string>
     */
    private readonly array $parameters;

    /**
     * @psalm-param class-string $className
     * @psalm-param array<string,string> $parameters
     */
    public function __construct(string $className, array $parameters)
    {
        $this->className  = $className;
        $this->parameters = $parameters;
    }

    /**
     * @psalm-return class-string
     */
    public function className(): string
    {
        return $this->className;
    }

    /**
     * @psalm-return array<string,string>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }
}
