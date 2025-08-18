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
final class TestDox extends Metadata
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $text;

    /**
     * @psalm-param 0|1 $level
     * @psalm-param non-empty-string $text
     */
    protected function __construct(int $level, string $text)
    {
        parent::__construct($level);

        $this->text = $text;
    }

    /**
     * @psalm-assert-if-true TestDox $this
     */
    public function isTestDox(): bool
    {
        return true;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function text(): string
    {
        return $this->text;
    }
}
