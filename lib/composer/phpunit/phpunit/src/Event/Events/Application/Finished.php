<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\Application;

use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class Finished implements Event
{
    private readonly Telemetry\Info $telemetryInfo;
    private readonly int $shellExitCode;

    public function __construct(Telemetry\Info $telemetryInfo, int $shellExitCode)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->shellExitCode = $shellExitCode;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    public function shellExitCode(): int
    {
        return $this->shellExitCode;
    }

    public function asString(): string
    {
        return sprintf(
            'PHPUnit Finished (Shell Exit Code: %d)',
            $this->shellExitCode,
        );
    }
}
