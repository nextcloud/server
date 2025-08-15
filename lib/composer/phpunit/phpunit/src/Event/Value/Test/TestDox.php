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

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class TestDox
{
    private readonly string $prettifiedClassName;
    private readonly string $prettifiedMethodName;
    private readonly string $prettifiedAndColorizedMethodName;

    public function __construct(string $prettifiedClassName, string $prettifiedMethodName, string $prettifiedAndColorizedMethodName)
    {
        $this->prettifiedClassName              = $prettifiedClassName;
        $this->prettifiedMethodName             = $prettifiedMethodName;
        $this->prettifiedAndColorizedMethodName = $prettifiedAndColorizedMethodName;
    }

    public function prettifiedClassName(): string
    {
        return $this->prettifiedClassName;
    }

    public function prettifiedMethodName(bool $colorize = false): string
    {
        if ($colorize) {
            return $this->prettifiedAndColorizedMethodName;
        }

        return $this->prettifiedMethodName;
    }
}
