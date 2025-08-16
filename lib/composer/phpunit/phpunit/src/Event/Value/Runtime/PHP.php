<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\Runtime;

use const PHP_EXTRA_VERSION;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_RELEASE_VERSION;
use const PHP_SAPI;
use const PHP_VERSION;
use const PHP_VERSION_ID;
use function array_merge;
use function get_loaded_extensions;
use function sort;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class PHP
{
    private readonly string $version;
    private readonly int $versionId;
    private readonly int $majorVersion;
    private readonly int $minorVersion;
    private readonly int $releaseVersion;
    private readonly string $extraVersion;
    private readonly string $sapi;

    /**
     * @psalm-var list<string>
     */
    private readonly array $extensions;

    public function __construct()
    {
        $this->version        = PHP_VERSION;
        $this->versionId      = PHP_VERSION_ID;
        $this->majorVersion   = PHP_MAJOR_VERSION;
        $this->minorVersion   = PHP_MINOR_VERSION;
        $this->releaseVersion = PHP_RELEASE_VERSION;
        $this->extraVersion   = PHP_EXTRA_VERSION;
        $this->sapi           = PHP_SAPI;

        $extensions = array_merge(
            get_loaded_extensions(true),
            get_loaded_extensions(),
        );

        sort($extensions);

        $this->extensions = $extensions;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function sapi(): string
    {
        return $this->sapi;
    }

    public function majorVersion(): int
    {
        return $this->majorVersion;
    }

    public function minorVersion(): int
    {
        return $this->minorVersion;
    }

    public function releaseVersion(): int
    {
        return $this->releaseVersion;
    }

    public function extraVersion(): string
    {
        return $this->extraVersion;
    }

    public function versionId(): int
    {
        return $this->versionId;
    }

    /**
     * @psalm-return list<string>
     */
    public function extensions(): array
    {
        return $this->extensions;
    }
}
