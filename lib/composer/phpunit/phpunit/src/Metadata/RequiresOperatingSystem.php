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
final class RequiresOperatingSystem extends Metadata
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $operatingSystem;

    /**
     * @psalm-param 0|1 $level
     * @psalm-param non-empty-string $operatingSystem
     */
    public function __construct(int $level, string $operatingSystem)
    {
        parent::__construct($level);

        $this->operatingSystem = $operatingSystem;
    }

    /**
     * @psalm-assert-if-true RequiresOperatingSystem $this
     */
    public function isRequiresOperatingSystem(): bool
    {
        return true;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function operatingSystem(): string
    {
        return $this->operatingSystem;
    }
}
