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

use function implode;
use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class TestStubForIntersectionOfInterfacesCreated implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    /**
     * @psalm-var list<class-string>
     */
    private readonly array $interfaces;

    /**
     * @psalm-param list<class-string> $interfaces
     */
    public function __construct(Telemetry\Info $telemetryInfo, array $interfaces)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->interfaces    = $interfaces;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    /**
     * @return list<class-string>
     */
    public function interfaces(): array
    {
        return $this->interfaces;
    }

    public function asString(): string
    {
        return sprintf(
            'Test Stub Created (%s)',
            implode('&', $this->interfaces),
        );
    }
}
