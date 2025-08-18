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
final class File
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $path;

    /**
     * @psalm-param non-empty-string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function path(): string
    {
        return $this->path;
    }
}
