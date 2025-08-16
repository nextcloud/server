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
final class ComparatorRegistered implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    /**
     * @psalm-var class-string
     */
    private readonly string $className;

    /**
     * @psalm-param class-string $className
     */
    public function __construct(Telemetry\Info $telemetryInfo, string $className)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->className     = $className;
    }

    public function telemetryInfo(): Telemetry\Info
    {
        return $this->telemetryInfo;
    }

    /**
     * @psalm-return class-string
     */
    public function className(): string
    {
        return $this->className;
    }

    public function asString(): string
    {
        return sprintf(
            'Comparator Registered (%s)',
            $this->className,
        );
    }
}
