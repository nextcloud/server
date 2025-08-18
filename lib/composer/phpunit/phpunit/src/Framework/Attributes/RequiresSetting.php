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
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class RequiresSetting
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $setting;

    /**
     * @psalm-var non-empty-string
     */
    private readonly string $value;

    /**
     * @psalm-param non-empty-string $setting
     * @psalm-param non-empty-string $value
     */
    public function __construct(string $setting, string $value)
    {
        $this->setting = $setting;
        $this->value   = $value;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function setting(): string
    {
        return $this->setting;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function value(): string
    {
        return $this->value;
    }
}
