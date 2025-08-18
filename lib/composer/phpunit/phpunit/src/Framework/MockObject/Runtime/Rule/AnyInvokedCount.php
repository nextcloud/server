<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework\MockObject\Rule;

use PHPUnit\Framework\MockObject\Invocation as BaseInvocation;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class AnyInvokedCount extends InvocationOrder
{
    public function toString(): string
    {
        return 'invoked zero or more times';
    }

    public function verify(): void
    {
    }

    public function matches(BaseInvocation $invocation): bool
    {
        return true;
    }
}
