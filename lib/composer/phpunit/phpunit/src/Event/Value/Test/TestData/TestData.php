<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\TestData;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
abstract class TestData
{
    private readonly string $data;

    protected function __construct(string $data)
    {
        $this->data = $data;
    }

    public function data(): string
    {
        return $this->data;
    }

    /**
     * @psalm-assert-if-true DataFromDataProvider $this
     */
    public function isFromDataProvider(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true DataFromTestDependency $this
     */
    public function isFromTestDependency(): bool
    {
        return false;
    }
}
