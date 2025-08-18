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

use function count;
use PHPUnit\Event\Test\AfterLastTestMethodErrored;
use PHPUnit\Event\Test\BeforeFirstTestMethodErrored;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\MarkedIncomplete;
use PHPUnit\Event\Test\PhpunitDeprecationTriggered;
use PHPUnit\Event\Test\PhpunitErrorTriggered;
use PHPUnit\Event\Test\PhpunitWarningTriggered;
use PHPUnit\Event\Test\Skipped as TestSkipped;
use PHPUnit\Event\TestRunner\DeprecationTriggered as TestRunnerDeprecationTriggered;
use PHPUnit\Event\TestRunner\WarningTriggered as TestRunnerWarningTriggered;
use PHPUnit\Event\TestSuite\Skipped as TestSuiteSkipped;
use PHPUnit\TestRunner\TestResult\Issues\Issue;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class TestResult
{
    private readonly int $numberOfTests;
    private readonly int $numberOfTestsRun;
    private readonly int $numberOfAssertions;

    /**
     * @psalm-var list<AfterLastTestMethodErrored|BeforeFirstTestMethodErrored|Errored>
     */
    private readonly array $testErroredEvents;

    /**
     * @psalm-var list<Failed>
     */
    private readonly array $testFailedEvents;

    /**
     * @psalm-var list<MarkedIncomplete>
     */
    private readonly array $testMarkedIncompleteEvents;

    /**
     * @psalm-var list<TestSuiteSkipped>
     */
    private readonly array $testSuiteSkippedEvents;

    /**
     * @psalm-var list<TestSkipped>
     */
    private readonly array $testSkippedEvents;

    /**
     * @psalm-var array<string,list<ConsideredRisky>>
     */
    private readonly array $testConsideredRiskyEvents;

    /**
     * @psalm-var array<string,list<PhpunitDeprecationTriggered>>
     */
    private readonly array $testTriggeredPhpunitDeprecationEvents;

    /**
     * @psalm-var array<string,list<PhpunitErrorTriggered>>
     */
    private readonly array $testTriggeredPhpunitErrorEvents;

    /**
     * @psalm-var array<string,list<PhpunitWarningTriggered>>
     */
    private readonly array $testTriggeredPhpunitWarningEvents;

    /**
     * @psalm-var list<TestRunnerDeprecationTriggered>
     */
    private readonly array $testRunnerTriggeredDeprecationEvents;

    /**
     * @psalm-var list<TestRunnerWarningTriggered>
     */
    private readonly array $testRunnerTriggeredWarningEvents;

    /**
     * @psalm-var list<Issue>
     */
    private readonly array $errors;

    /**
     * @psalm-var list<Issue>
     */
    private readonly array $deprecations;

    /**
     * @psalm-var list<Issue>
     */
    private readonly array $notices;

    /**
     * @psalm-var list<Issue>
     */
    private readonly array $warnings;

    /**
     * @psalm-var list<Issue>
     */
    private readonly array $phpDeprecations;

    /**
     * @psalm-var list<Issue>
     */
    private readonly array $phpNotices;

    /**
     * @psalm-var list<Issue>
     */
    private readonly array $phpWarnings;

    /**
     * @psalm-var non-negative-int
     */
    private readonly int $numberOfIssuesIgnoredByBaseline;

    /**
     * @psalm-param list<AfterLastTestMethodErrored|BeforeFirstTestMethodErrored|Errored> $testErroredEvents
     * @psalm-param list<Failed> $testFailedEvents
     * @psalm-param array<string,list<ConsideredRisky>> $testConsideredRiskyEvents
     * @psalm-param list<TestSuiteSkipped> $testSuiteSkippedEvents
     * @psalm-param list<TestSkipped> $testSkippedEvents
     * @psalm-param list<MarkedIncomplete> $testMarkedIncompleteEvents
     * @psalm-param array<string,list<PhpunitDeprecationTriggered>> $testTriggeredPhpunitDeprecationEvents
     * @psalm-param array<string,list<PhpunitErrorTriggered>> $testTriggeredPhpunitErrorEvents
     * @psalm-param array<string,list<PhpunitWarningTriggered>> $testTriggeredPhpunitWarningEvents
     * @psalm-param list<TestRunnerDeprecationTriggered> $testRunnerTriggeredDeprecationEvents
     * @psalm-param list<TestRunnerWarningTriggered> $testRunnerTriggeredWarningEvents
     * @psalm-param list<Issue> $errors
     * @psalm-param list<Issue> $deprecations
     * @psalm-param list<Issue> $notices
     * @psalm-param list<Issue> $warnings
     * @psalm-param list<Issue> $phpDeprecations
     * @psalm-param list<Issue> $phpNotices
     * @psalm-param list<Issue> $phpWarnings
     * @psalm-param non-negative-int $numberOfIssuesIgnoredByBaseline
     */
    public function __construct(int $numberOfTests, int $numberOfTestsRun, int $numberOfAssertions, array $testErroredEvents, array $testFailedEvents, array $testConsideredRiskyEvents, array $testSuiteSkippedEvents, array $testSkippedEvents, array $testMarkedIncompleteEvents, array $testTriggeredPhpunitDeprecationEvents, array $testTriggeredPhpunitErrorEvents, array $testTriggeredPhpunitWarningEvents, array $testRunnerTriggeredDeprecationEvents, array $testRunnerTriggeredWarningEvents, array $errors, array $deprecations, array $notices, array $warnings, array $phpDeprecations, array $phpNotices, array $phpWarnings, int $numberOfIssuesIgnoredByBaseline)
    {
        $this->numberOfTests                         = $numberOfTests;
        $this->numberOfTestsRun                      = $numberOfTestsRun;
        $this->numberOfAssertions                    = $numberOfAssertions;
        $this->testErroredEvents                     = $testErroredEvents;
        $this->testFailedEvents                      = $testFailedEvents;
        $this->testConsideredRiskyEvents             = $testConsideredRiskyEvents;
        $this->testSuiteSkippedEvents                = $testSuiteSkippedEvents;
        $this->testSkippedEvents                     = $testSkippedEvents;
        $this->testMarkedIncompleteEvents            = $testMarkedIncompleteEvents;
        $this->testTriggeredPhpunitDeprecationEvents = $testTriggeredPhpunitDeprecationEvents;
        $this->testTriggeredPhpunitErrorEvents       = $testTriggeredPhpunitErrorEvents;
        $this->testTriggeredPhpunitWarningEvents     = $testTriggeredPhpunitWarningEvents;
        $this->testRunnerTriggeredDeprecationEvents  = $testRunnerTriggeredDeprecationEvents;
        $this->testRunnerTriggeredWarningEvents      = $testRunnerTriggeredWarningEvents;
        $this->errors                                = $errors;
        $this->deprecations                          = $deprecations;
        $this->notices                               = $notices;
        $this->warnings                              = $warnings;
        $this->phpDeprecations                       = $phpDeprecations;
        $this->phpNotices                            = $phpNotices;
        $this->phpWarnings                           = $phpWarnings;
        $this->numberOfIssuesIgnoredByBaseline       = $numberOfIssuesIgnoredByBaseline;
    }

    public function numberOfTestsRun(): int
    {
        return $this->numberOfTestsRun;
    }

    public function numberOfAssertions(): int
    {
        return $this->numberOfAssertions;
    }

    /**
     * @psalm-return list<AfterLastTestMethodErrored|BeforeFirstTestMethodErrored|Errored>
     */
    public function testErroredEvents(): array
    {
        return $this->testErroredEvents;
    }

    public function numberOfTestErroredEvents(): int
    {
        return count($this->testErroredEvents);
    }

    public function hasTestErroredEvents(): bool
    {
        return $this->numberOfTestErroredEvents() > 0;
    }

    /**
     * @psalm-return list<Failed>
     */
    public function testFailedEvents(): array
    {
        return $this->testFailedEvents;
    }

    public function numberOfTestFailedEvents(): int
    {
        return count($this->testFailedEvents);
    }

    public function hasTestFailedEvents(): bool
    {
        return $this->numberOfTestFailedEvents() > 0;
    }

    /**
     * @psalm-return array<string,list<ConsideredRisky>>
     */
    public function testConsideredRiskyEvents(): array
    {
        return $this->testConsideredRiskyEvents;
    }

    public function numberOfTestsWithTestConsideredRiskyEvents(): int
    {
        return count($this->testConsideredRiskyEvents);
    }

    public function hasTestConsideredRiskyEvents(): bool
    {
        return $this->numberOfTestsWithTestConsideredRiskyEvents() > 0;
    }

    /**
     * @psalm-return list<TestSuiteSkipped>
     */
    public function testSuiteSkippedEvents(): array
    {
        return $this->testSuiteSkippedEvents;
    }

    public function numberOfTestSuiteSkippedEvents(): int
    {
        return count($this->testSuiteSkippedEvents);
    }

    public function hasTestSuiteSkippedEvents(): bool
    {
        return $this->numberOfTestSuiteSkippedEvents() > 0;
    }

    /**
     * @psalm-return list<TestSkipped>
     */
    public function testSkippedEvents(): array
    {
        return $this->testSkippedEvents;
    }

    public function numberOfTestSkippedEvents(): int
    {
        return count($this->testSkippedEvents);
    }

    public function hasTestSkippedEvents(): bool
    {
        return $this->numberOfTestSkippedEvents() > 0;
    }

    /**
     * @psalm-return list<MarkedIncomplete>
     */
    public function testMarkedIncompleteEvents(): array
    {
        return $this->testMarkedIncompleteEvents;
    }

    public function numberOfTestMarkedIncompleteEvents(): int
    {
        return count($this->testMarkedIncompleteEvents);
    }

    public function hasTestMarkedIncompleteEvents(): bool
    {
        return $this->numberOfTestMarkedIncompleteEvents() > 0;
    }

    /**
     * @psalm-return array<string,list<PhpunitDeprecationTriggered>>
     */
    public function testTriggeredPhpunitDeprecationEvents(): array
    {
        return $this->testTriggeredPhpunitDeprecationEvents;
    }

    public function numberOfTestsWithTestTriggeredPhpunitDeprecationEvents(): int
    {
        return count($this->testTriggeredPhpunitDeprecationEvents);
    }

    public function hasTestTriggeredPhpunitDeprecationEvents(): bool
    {
        return $this->numberOfTestsWithTestTriggeredPhpunitDeprecationEvents() > 0;
    }

    /**
     * @psalm-return array<string,list<PhpunitErrorTriggered>>
     */
    public function testTriggeredPhpunitErrorEvents(): array
    {
        return $this->testTriggeredPhpunitErrorEvents;
    }

    public function numberOfTestsWithTestTriggeredPhpunitErrorEvents(): int
    {
        return count($this->testTriggeredPhpunitErrorEvents);
    }

    public function hasTestTriggeredPhpunitErrorEvents(): bool
    {
        return $this->numberOfTestsWithTestTriggeredPhpunitErrorEvents() > 0;
    }

    /**
     * @psalm-return array<string,list<PhpunitWarningTriggered>>
     */
    public function testTriggeredPhpunitWarningEvents(): array
    {
        return $this->testTriggeredPhpunitWarningEvents;
    }

    public function numberOfTestsWithTestTriggeredPhpunitWarningEvents(): int
    {
        return count($this->testTriggeredPhpunitWarningEvents);
    }

    public function hasTestTriggeredPhpunitWarningEvents(): bool
    {
        return $this->numberOfTestsWithTestTriggeredPhpunitWarningEvents() > 0;
    }

    /**
     * @psalm-return list<TestRunnerDeprecationTriggered>
     */
    public function testRunnerTriggeredDeprecationEvents(): array
    {
        return $this->testRunnerTriggeredDeprecationEvents;
    }

    public function numberOfTestRunnerTriggeredDeprecationEvents(): int
    {
        return count($this->testRunnerTriggeredDeprecationEvents);
    }

    public function hasTestRunnerTriggeredDeprecationEvents(): bool
    {
        return $this->numberOfTestRunnerTriggeredDeprecationEvents() > 0;
    }

    /**
     * @psalm-return list<TestRunnerWarningTriggered>
     */
    public function testRunnerTriggeredWarningEvents(): array
    {
        return $this->testRunnerTriggeredWarningEvents;
    }

    public function numberOfTestRunnerTriggeredWarningEvents(): int
    {
        return count($this->testRunnerTriggeredWarningEvents);
    }

    public function hasTestRunnerTriggeredWarningEvents(): bool
    {
        return $this->numberOfTestRunnerTriggeredWarningEvents() > 0;
    }

    public function wasSuccessful(): bool
    {
        return !$this->hasTestErroredEvents() &&
               !$this->hasTestFailedEvents() &&
               !$this->hasTestTriggeredPhpunitErrorEvents();
    }

    public function wasSuccessfulAndNoTestHasIssues(): bool
    {
        return $this->wasSuccessful() && !$this->hasTestsWithIssues();
    }

    public function hasIssues(): bool
    {
        return $this->hasTestsWithIssues() ||
               $this->hasTestRunnerTriggeredWarningEvents();
    }

    public function hasTestsWithIssues(): bool
    {
        return $this->hasRiskyTests() ||
               $this->hasIncompleteTests() ||
               $this->hasDeprecations() ||
               !empty($this->errors) ||
               $this->hasNotices() ||
               $this->hasWarnings() ||
               $this->hasPhpunitWarnings();
    }

    /**
     * @psalm-return list<Issue>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @psalm-return list<Issue>
     */
    public function deprecations(): array
    {
        return $this->deprecations;
    }

    /**
     * @psalm-return list<Issue>
     */
    public function notices(): array
    {
        return $this->notices;
    }

    /**
     * @psalm-return list<Issue>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * @psalm-return list<Issue>
     */
    public function phpDeprecations(): array
    {
        return $this->phpDeprecations;
    }

    /**
     * @psalm-return list<Issue>
     */
    public function phpNotices(): array
    {
        return $this->phpNotices;
    }

    /**
     * @psalm-return list<Issue>
     */
    public function phpWarnings(): array
    {
        return $this->phpWarnings;
    }

    public function hasTests(): bool
    {
        return $this->numberOfTests > 0;
    }

    public function hasErrors(): bool
    {
        return $this->numberOfErrors() > 0;
    }

    public function numberOfErrors(): int
    {
        return $this->numberOfTestErroredEvents() +
               count($this->errors) +
               $this->numberOfTestsWithTestTriggeredPhpunitErrorEvents();
    }

    public function hasDeprecations(): bool
    {
        return $this->numberOfDeprecations() > 0;
    }

    public function hasPhpOrUserDeprecations(): bool
    {
        return $this->numberOfPhpOrUserDeprecations() > 0;
    }

    public function numberOfPhpOrUserDeprecations(): int
    {
        return count($this->deprecations) +
               count($this->phpDeprecations);
    }

    public function hasPhpunitDeprecations(): bool
    {
        return $this->numberOfPhpunitDeprecations() > 0;
    }

    public function numberOfPhpunitDeprecations(): int
    {
        return count($this->testTriggeredPhpunitDeprecationEvents) +
               count($this->testRunnerTriggeredDeprecationEvents);
    }

    public function hasPhpunitWarnings(): bool
    {
        return $this->numberOfPhpunitWarnings() > 0;
    }

    public function numberOfPhpunitWarnings(): int
    {
        return count($this->testTriggeredPhpunitWarningEvents) +
               count($this->testRunnerTriggeredWarningEvents);
    }

    public function numberOfDeprecations(): int
    {
        return count($this->deprecations) +
               count($this->phpDeprecations) +
               count($this->testTriggeredPhpunitDeprecationEvents) +
               count($this->testRunnerTriggeredDeprecationEvents);
    }

    public function hasNotices(): bool
    {
        return $this->numberOfNotices() > 0;
    }

    public function numberOfNotices(): int
    {
        return count($this->notices) +
               count($this->phpNotices);
    }

    public function hasWarnings(): bool
    {
        return $this->numberOfWarnings() > 0;
    }

    public function numberOfWarnings(): int
    {
        return count($this->warnings) +
               count($this->phpWarnings);
    }

    public function hasIncompleteTests(): bool
    {
        return !empty($this->testMarkedIncompleteEvents);
    }

    public function hasRiskyTests(): bool
    {
        return !empty($this->testConsideredRiskyEvents);
    }

    public function hasSkippedTests(): bool
    {
        return !empty($this->testSkippedEvents);
    }

    public function hasIssuesIgnoredByBaseline(): bool
    {
        return $this->numberOfIssuesIgnoredByBaseline > 0;
    }

    /**
     * @psalm-return non-negative-int
     */
    public function numberOfIssuesIgnoredByBaseline(): int
    {
        return $this->numberOfIssuesIgnoredByBaseline;
    }
}
