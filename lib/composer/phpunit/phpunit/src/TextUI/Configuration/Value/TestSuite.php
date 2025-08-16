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
final class TestSuite
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $name;
    private readonly TestDirectoryCollection $directories;
    private readonly TestFileCollection $files;
    private readonly FileCollection $exclude;

    /**
     * @psalm-param non-empty-string $name
     */
    public function __construct(string $name, TestDirectoryCollection $directories, TestFileCollection $files, FileCollection $exclude)
    {
        $this->name        = $name;
        $this->directories = $directories;
        $this->files       = $files;
        $this->exclude     = $exclude;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function directories(): TestDirectoryCollection
    {
        return $this->directories;
    }

    public function files(): TestFileCollection
    {
        return $this->files;
    }

    public function exclude(): FileCollection
    {
        return $this->exclude;
    }
}
