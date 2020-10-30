<?php

namespace Amp\Internal;

/**
 * Formats a stacktrace obtained via `debug_backtrace()`.
 *
 * @param array<array{file?: string, line: int, type?: string, class: string, function: string}> $trace Output of
 *     `debug_backtrace()`.
 *
 * @return string Formatted stacktrace.
 *
 * @codeCoverageIgnore
 * @internal
 */
function formatStacktrace(array $trace): string
{
    return \implode("\n", \array_map(static function ($e, $i) {
        $line = "#{$i} ";

        if (isset($e["file"])) {
            $line .= "{$e['file']}:{$e['line']} ";
        }

        if (isset($e["type"])) {
            $line .= $e["class"] . $e["type"];
        }

        return $line . $e["function"] . "()";
    }, $trace, \array_keys($trace)));
}

/**
 * Creates a `TypeError` with a standardized error message.
 *
 * @param string[] $expected Expected types.
 * @param mixed    $given Given value.
 *
 * @return \TypeError
 *
 * @internal
 */
function createTypeError(array $expected, $given): \TypeError
{
    $givenType = \is_object($given) ? \sprintf("instance of %s", \get_class($given)) : \gettype($given);

    if (\count($expected) === 1) {
        $expectedType = "Expected the following type: " . \array_pop($expected);
    } else {
        $expectedType = "Expected one of the following types: " . \implode(", ", $expected);
    }

    return new \TypeError("{$expectedType}; {$givenType} given");
}

/**
 * Returns the current time relative to an arbitrary point in time.
 *
 * @return int Time in milliseconds.
 */
function getCurrentTime(): int
{
    /** @var int|null $startTime */
    static $startTime;
    /** @var int|null $nextWarning */
    static $nextWarning;

    if (\PHP_INT_SIZE === 4) {
        // @codeCoverageIgnoreStart
        if ($startTime === null) {
            $startTime = \PHP_VERSION_ID >= 70300 ? \hrtime(false)[0] : \time();
            $nextWarning = \PHP_INT_MAX - 86400 * 7;
        }

        if (\PHP_VERSION_ID >= 70300) {
            list($seconds, $nanoseconds) = \hrtime(false);
            $seconds -= $startTime;

            if ($seconds >= $nextWarning) {
                $timeToOverflow = (\PHP_INT_MAX - $seconds * 1000) / 1000;
                \trigger_error(
                    "getCurrentTime() will overflow in $timeToOverflow seconds, please restart the process before that. " .
                    "You're using a 32 bit version of PHP, so time will overflow about every 24 days. Regular restarts are required.",
                    \E_USER_WARNING
                );

                /** @psalm-suppress PossiblyNullOperand */
                $nextWarning += 600; // every 10 minutes
            }

            return (int) ($seconds * 1000 + $nanoseconds / 1000000);
        }

        $seconds = \microtime(true) - $startTime;
        if ($seconds >= $nextWarning) {
            $timeToOverflow = (\PHP_INT_MAX - $seconds * 1000) / 1000;
            \trigger_error(
                "getCurrentTime() will overflow in $timeToOverflow seconds, please restart the process before that. " .
                "You're using a 32 bit version of PHP, so time will overflow about every 24 days. Regular restarts are required.",
                \E_USER_WARNING
            );

            /** @psalm-suppress PossiblyNullOperand */
            $nextWarning += 600; // every 10 minutes
        }

        return (int) ($seconds * 1000);
        // @codeCoverageIgnoreEnd
    }

    if (\PHP_VERSION_ID >= 70300) {
        list($seconds, $nanoseconds) = \hrtime(false);
        return (int) ($seconds * 1000 + $nanoseconds / 1000000);
    }

    return (int) (\microtime(true) * 1000);
}
