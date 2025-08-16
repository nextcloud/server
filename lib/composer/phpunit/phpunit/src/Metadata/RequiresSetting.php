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
final class RequiresSetting extends Metadata
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
     * @psalm-param 0|1 $level
     * @psalm-param non-empty-string $setting
     * @psalm-param non-empty-string $value
     */
    protected function __construct(int $level, string $setting, string $value)
    {
        parent::__construct($level);

        $this->setting = $setting;
        $this->value   = $value;
    }

    /**
     * @psalm-assert-if-true RequiresSetting $this
     */
    public function isRequiresSetting(): bool
    {
        return true;
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
