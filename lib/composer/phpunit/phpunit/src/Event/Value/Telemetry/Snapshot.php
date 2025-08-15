<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\Telemetry;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class Snapshot
{
    private readonly HRTime $time;
    private readonly MemoryUsage $memoryUsage;
    private readonly MemoryUsage $peakMemoryUsage;
    private readonly GarbageCollectorStatus $garbageCollectorStatus;

    public function __construct(HRTime $time, MemoryUsage $memoryUsage, MemoryUsage $peakMemoryUsage, GarbageCollectorStatus $garbageCollectorStatus)
    {
        $this->time                   = $time;
        $this->memoryUsage            = $memoryUsage;
        $this->peakMemoryUsage        = $peakMemoryUsage;
        $this->garbageCollectorStatus = $garbageCollectorStatus;
    }

    public function time(): HRTime
    {
        return $this->time;
    }

    public function memoryUsage(): MemoryUsage
    {
        return $this->memoryUsage;
    }

    public function peakMemoryUsage(): MemoryUsage
    {
        return $this->peakMemoryUsage;
    }

    public function garbageCollectorStatus(): GarbageCollectorStatus
    {
        return $this->garbageCollectorStatus;
    }
}
