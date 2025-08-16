<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-invoker.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Invoker;

use const SIGALRM;
use function call_user_func_array;
use function function_exists;
use function pcntl_alarm;
use function pcntl_async_signals;
use function pcntl_signal;
use function sprintf;
use Throwable;

final class Invoker
{
    private int $timeout;

    /**
     * @throws Throwable
     */
    public function invoke(callable $callable, array $arguments, int $timeout): mixed
    {
        if (!$this->canInvokeWithTimeout()) {
            throw new ProcessControlExtensionNotLoadedException(
                'The pcntl (process control) extension for PHP is required'
            );
        }

        pcntl_signal(
            SIGALRM,
            function (): void
            {
                throw new TimeoutException(
                    sprintf(
                        'Execution aborted after %d second%s',
                        $this->timeout,
                        $this->timeout === 1 ? '' : 's'
                    )
                );
            },
            true
        );

        $this->timeout = $timeout;

        pcntl_async_signals(true);
        pcntl_alarm($timeout);

        try {
            return call_user_func_array($callable, $arguments);
        } finally {
            pcntl_alarm(0);
        }
    }

    public function canInvokeWithTimeout(): bool
    {
        return function_exists('pcntl_signal') && function_exists('pcntl_async_signals') && function_exists('pcntl_alarm');
    }
}
