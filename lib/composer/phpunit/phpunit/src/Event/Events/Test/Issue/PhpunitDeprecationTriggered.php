<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\Test;

use const PHP_EOL;
use function sprintf;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class PhpunitDeprecationTriggered implements Event
{
    private readonly Telemetry\Info $telemetryInfo;
    private readonly Test $test;

    /**
     * @psalm-var non-empty-string
     */
    private readonly string $message;

    /**
     * @psalm-param non-empty-string $message
     */
    public function __construct(Telemetry\Info $telemetryInfo, Test $test, string $message)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->test          = $test;
        $this->message       = $message;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    public function test(): Test
    {
        return $this->test;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function message(): string
    {
        return $this->message;
    }

    public function asString(): string
    {
        $message = $this->message;

        if (!empty($message)) {
            $message = PHP_EOL . $message;
        }

        return sprintf(
            'Test Triggered PHPUnit Deprecation (%s)%s',
            $this->test->id(),
            $message,
        );
    }
}
