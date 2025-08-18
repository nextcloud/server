<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event\TestRunner;

use function sprintf;
use PHPUnit\Event\Event;
use PHPUnit\Event\Telemetry;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class ExtensionBootstrapped implements Event
{
    private readonly Telemetry\Info $telemetryInfo;

    /**
     * @psalm-var class-string
     */
    private readonly string $className;

    /**
     * @psalm-var array<string, string>
     */
    private readonly array $parameters;

    /**
     * @psalm-param class-string $className
     * @psalm-param array<string, string> $parameters
     */
    public function __construct(Telemetry\Info $telemetryInfo, string $className, array $parameters)
    {
        $this->telemetryInfo = $telemetryInfo;
        $this->className     = $className;
        $this->parameters    = $parameters;
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

    /**
     * @psalm-return array<string, string>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    public function asString(): string
    {
        return sprintf(
            'Extension Bootstrapped (%s)',
            $this->className,
        );
    }
}
