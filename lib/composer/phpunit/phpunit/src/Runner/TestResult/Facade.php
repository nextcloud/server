<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TestRunner\TestResult;

use PHPUnit\Event\EventFacadeIsSealedException;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Event\UnknownSubscriberTypeException;
use PHPUnit\TextUI\Configuration\Registry as ConfigurationRegistry;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class Facade
{
    private static ?Collector $collector = null;

    /**
     * @throws EventFacadeIsSealedException
     * @throws UnknownSubscriberTypeException
     */
    public static function init(): void
    {
        self::collector();
    }

    /**
     * @throws EventFacadeIsSealedException
     * @throws UnknownSubscriberTypeException
     */
    public static function result(): TestResult
    {
        return self::collector()->result();
    }

    /**
     * @throws EventFacadeIsSealedException
     * @throws UnknownSubscriberTypeException
     */
    public static function shouldStop(): bool
    {
        $configuration = ConfigurationRegistry::get();
        $collector     = self::collector();

        if (($configuration->stopOnDefect() || $configuration->stopOnError()) && $collector->hasErroredTests()) {
            return true;
        }

        if (($configuration->stopOnDefect() || $configuration->stopOnFailure()) && $collector->hasFailedTests()) {
            return true;
        }

        if (($configuration->stopOnDefect() || $configuration->stopOnWarning()) && $collector->hasWarnings()) {
            return true;
        }

        if (($configuration->stopOnDefect() || $configuration->stopOnRisky()) && $collector->hasRiskyTests()) {
            return true;
        }

        if ($configuration->stopOnDeprecation() && $collector->hasDeprecations()) {
            return true;
        }

        if ($configuration->stopOnNotice() && $collector->hasNotices()) {
            return true;
        }

        if ($configuration->stopOnIncomplete() && $collector->hasIncompleteTests()) {
            return true;
        }

        if ($configuration->stopOnSkipped() && $collector->hasSkippedTests()) {
            return true;
        }

        return false;
    }

    /**
     * @throws EventFacadeIsSealedException
     * @throws UnknownSubscriberTypeException
     */
    private static function collector(): Collector
    {
        if (self::$collector === null) {
            $configuration = ConfigurationRegistry::get();

            self::$collector = new Collector(
                EventFacade::instance(),
                $configuration->source(),
            );
        }

        return self::$collector;
    }
}
