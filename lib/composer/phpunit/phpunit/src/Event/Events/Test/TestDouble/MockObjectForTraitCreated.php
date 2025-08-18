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

use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class MockObjectForTraitCreated implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    /**
     * @psalm-var trait-string
     */
    private readonly string $traitName;

    /**
     * @psalm-param trait-string $traitName
     */
    public function __construct(Telemetry\Info $telemetryInfo, string $traitName)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->traitName     = $traitName;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    /**
     * @psalm-return trait-string
     */
    public function traitName(): string
    {
        return $this->traitName;
    }

    public function asString(): string
    {
        return sprintf(
            'Mock Object Created (%s)',
            $this->traitName,
        );
    }
}
