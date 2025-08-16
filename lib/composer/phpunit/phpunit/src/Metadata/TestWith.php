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
final class TestWith extends Metadata
{
    private readonly mixed $data;

    /**
     * @psalm-param 0|1 $level
     */
    protected function __construct(int $level, mixed $data)
    {
        parent::__construct($level);

        $this->data = $data;
    }

    /**
     * @psalm-assert-if-true TestWith $this
     */
    public function isTestWith(): bool
    {
        return true;
    }

    public function data(): mixed
    {
        return $this->data;
    }
}
