<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Runner;

use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;
use function defined;
use function error_reporting;
use function restore_error_handler;
use function set_error_handler;
use PHPUnit\Event;
use PHPUnit\Event\Code\NoTestCaseObjectOnCallStackException;
use PHPUnit\Runner\Baseline\Baseline;
use PHPUnit\Runner\Baseline\Issue;
use PHPUnit\Util\ExcludeList;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class ErrorHandler
{
    private const UNHANDLEABLE_LEVELS         = E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING;
    private const INSUPPRESSIBLE_LEVELS       = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
    private static ?self $instance            = null;
    private ?Baseline $baseline               = null;
    private bool $enabled                     = false;
    private ?int $originalErrorReportingLevel = null;

    public static function instance(): self
    {
        return self::$instance ?? self::$instance = new self;
    }

    /**
     * @throws NoTestCaseObjectOnCallStackException
     */
    public function __invoke(int $errorNumber, string $errorString, string $errorFile, int $errorLine): bool
    {
        $suppressed = (error_reporting() & ~self::INSUPPRESSIBLE_LEVELS) === 0;

        if ($suppressed && (new ExcludeList)->isExcluded($errorFile)) {
            return false;
        }

        /**
         * E_STRICT is deprecated since PHP 8.4.
         *
         * @see https://github.com/sebastianbergmann/phpunit/issues/5956
         */
        if (defined('E_STRICT') && $errorNumber === 2048) {
            $errorNumber = E_NOTICE;
        }

        $test = Event\Code\TestMethodBuilder::fromCallStack();

        $ignoredByBaseline = $this->ignoredByBaseline($errorFile, $errorLine, $errorString);
        $ignoredByTest     = $test->metadata()->isIgnoreDeprecations()->isNotEmpty();

        switch ($errorNumber) {
            case E_NOTICE:
                Event\Facade::emitter()->testTriggeredPhpNotice(
                    $test,
                    $errorString,
                    $errorFile,
                    $errorLine,
                    $suppressed,
                    $ignoredByBaseline,
                );

                break;

            case E_USER_NOTICE:
                Event\Facade::emitter()->testTriggeredNotice(
                    $test,
                    $errorString,
                    $errorFile,
                    $errorLine,
                    $suppressed,
                    $ignoredByBaseline,
                );

                break;

            case E_WARNING:
                Event\Facade::emitter()->testTriggeredPhpWarning(
                    $test,
                    $errorString,
                    $errorFile,
                    $errorLine,
                    $suppressed,
                    $ignoredByBaseline,
                );

                break;

            case E_USER_WARNING:
                Event\Facade::emitter()->testTriggeredWarning(
                    $test,
                    $errorString,
                    $errorFile,
                    $errorLine,
                    $suppressed,
                    $ignoredByBaseline,
                );

                break;

            case E_DEPRECATED:
                Event\Facade::emitter()->testTriggeredPhpDeprecation(
                    $test,
                    $errorString,
                    $errorFile,
                    $errorLine,
                    $suppressed,
                    $ignoredByBaseline,
                    $ignoredByTest,
                );

                break;

            case E_USER_DEPRECATED:
                Event\Facade::emitter()->testTriggeredDeprecation(
                    $test,
                    $errorString,
                    $errorFile,
                    $errorLine,
                    $suppressed,
                    $ignoredByBaseline,
                    $ignoredByTest,
                );

                break;

            case E_USER_ERROR:
                Event\Facade::emitter()->testTriggeredError(
                    $test,
                    $errorString,
                    $errorFile,
                    $errorLine,
                    $suppressed,
                );

                throw new ErrorException('E_USER_ERROR was triggered');

            default:
                return false;
        }

        return false;
    }

    public function enable(): void
    {
        if ($this->enabled) {
            return;
        }

        $oldErrorHandler = set_error_handler($this);

        if ($oldErrorHandler !== null) {
            restore_error_handler();

            return;
        }

        $this->enabled                     = true;
        $this->originalErrorReportingLevel = error_reporting();

        error_reporting($this->originalErrorReportingLevel & self::UNHANDLEABLE_LEVELS);
    }

    public function disable(): void
    {
        if (!$this->enabled) {
            return;
        }

        restore_error_handler();

        error_reporting(error_reporting() | $this->originalErrorReportingLevel);

        $this->enabled                     = false;
        $this->originalErrorReportingLevel = null;
    }

    public function use(Baseline $baseline): void
    {
        $this->baseline = $baseline;
    }

    /**
     * @psalm-param non-empty-string $file
     * @psalm-param positive-int $line
     * @psalm-param non-empty-string $description
     */
    private function ignoredByBaseline(string $file, int $line, string $description): bool
    {
        if ($this->baseline === null) {
            return false;
        }

        return $this->baseline->has(Issue::from($file, $line, null, $description));
    }
}
