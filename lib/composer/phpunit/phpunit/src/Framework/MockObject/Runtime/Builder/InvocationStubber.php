<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework\MockObject\Builder;

use PHPUnit\Framework\MockObject\Stub\Stub;
use Throwable;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
interface InvocationStubber
{
    public function will(Stub $stub): Identity;

    public function willReturn(mixed $value, mixed ...$nextValues): self;

    public function willReturnReference(mixed &$reference): self;

    /**
     * @psalm-param array<int, array<int, mixed>> $valueMap
     */
    public function willReturnMap(array $valueMap): self;

    public function willReturnArgument(int $argumentIndex): self;

    public function willReturnCallback(callable $callback): self;

    public function willReturnSelf(): self;

    public function willReturnOnConsecutiveCalls(mixed ...$values): self;

    public function willThrowException(Throwable $exception): self;
}
