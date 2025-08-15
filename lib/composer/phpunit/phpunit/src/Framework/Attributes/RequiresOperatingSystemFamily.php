<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework\Attributes;

use Attribute;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RequiresOperatingSystemFamily
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $operatingSystemFamily;

    /**
     * @psalm-param non-empty-string $operatingSystemFamily
     */
    public function __construct(string $operatingSystemFamily)
    {
        $this->operatingSystemFamily = $operatingSystemFamily;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function operatingSystemFamily(): string
    {
        return $this->operatingSystemFamily;
    }
}
