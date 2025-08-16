<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\XmlConfiguration;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 *
 * @psalm-immutable
 */
final class PHPUnit
{
    private readonly ?string $cacheDirectory;
    private readonly bool $cacheResult;
    private readonly ?string $cacheResultFile;
    private readonly int|string $columns;
    private readonly string $colors;
    private readonly bool $stderr;
    private readonly bool $displayDetailsOnAllIssues;
    private readonly bool $displayDetailsOnIncompleteTests;
    private readonly bool $displayDetailsOnSkippedTests;
    private readonly bool $displayDetailsOnTestsThatTriggerDeprecations;
    private readonly bool $displayDetailsOnPhpunitDeprecations;
    private readonly bool $displayDetailsOnTestsThatTriggerErrors;
    private readonly bool $displayDetailsOnTestsThatTriggerNotices;
    private readonly bool $displayDetailsOnTestsThatTriggerWarnings;
    private readonly bool $reverseDefectList;
    private readonly bool $requireCoverageMetadata;
    private readonly ?string $bootstrap;
    private readonly bool $processIsolation;
    private readonly bool $failOnAllIssues;
    private readonly bool $failOnDeprecation;
    private readonly bool $failOnPhpunitDeprecation;
    private readonly bool $failOnPhpunitWarning;
    private readonly bool $failOnEmptyTestSuite;
    private readonly bool $failOnIncomplete;
    private readonly bool $failOnNotice;
    private readonly bool $failOnRisky;
    private readonly bool $failOnSkipped;
    private readonly bool $failOnWarning;
    private readonly bool $stopOnDefect;
    private readonly bool $stopOnDeprecation;
    private readonly bool $stopOnError;
    private readonly bool $stopOnFailure;
    private readonly bool $stopOnIncomplete;
    private readonly bool $stopOnNotice;
    private readonly bool $stopOnRisky;
    private readonly bool $stopOnSkipped;
    private readonly bool $stopOnWarning;

    /**
     * @psalm-var ?non-empty-string
     */
    private readonly ?string $extensionsDirectory;
    private readonly bool $beStrictAboutChangesToGlobalState;
    private readonly bool $beStrictAboutOutputDuringTests;
    private readonly bool $beStrictAboutTestsThatDoNotTestAnything;
    private readonly bool $beStrictAboutCoverageMetadata;
    private readonly bool $enforceTimeLimit;
    private readonly int $defaultTimeLimit;
    private readonly int $timeoutForSmallTests;
    private readonly int $timeoutForMediumTests;
    private readonly int $timeoutForLargeTests;
    private readonly ?string $defaultTestSuite;
    private readonly int $executionOrder;
    private readonly bool $resolveDependencies;
    private readonly bool $defectsFirst;
    private readonly bool $backupGlobals;
    private readonly bool $backupStaticProperties;
    private readonly bool $registerMockObjectsFromTestArgumentsRecursively;
    private readonly bool $testdoxPrinter;
    private readonly bool $controlGarbageCollector;
    private readonly int $numberOfTestsBeforeGarbageCollection;

    /**
     * @psalm-param ?non-empty-string $extensionsDirectory
     */
    public function __construct(?string $cacheDirectory, bool $cacheResult, ?string $cacheResultFile, int|string $columns, string $colors, bool $stderr, bool $displayDetailsOnAllIssues, bool $displayDetailsOnIncompleteTests, bool $displayDetailsOnSkippedTests, bool $displayDetailsOnTestsThatTriggerDeprecations, bool $displayDetailsOnPhpunitDeprecations, bool $displayDetailsOnTestsThatTriggerErrors, bool $displayDetailsOnTestsThatTriggerNotices, bool $displayDetailsOnTestsThatTriggerWarnings, bool $reverseDefectList, bool $requireCoverageMetadata, ?string $bootstrap, bool $processIsolation, bool $failOnAllIssues, bool $failOnDeprecation, bool $failOnPhpunitDeprecation, bool $failOnPhpunitWarning, bool $failOnEmptyTestSuite, bool $failOnIncomplete, bool $failOnNotice, bool $failOnRisky, bool $failOnSkipped, bool $failOnWarning, bool $stopOnDefect, bool $stopOnDeprecation, bool $stopOnError, bool $stopOnFailure, bool $stopOnIncomplete, bool $stopOnNotice, bool $stopOnRisky, bool $stopOnSkipped, bool $stopOnWarning, ?string $extensionsDirectory, bool $beStrictAboutChangesToGlobalState, bool $beStrictAboutOutputDuringTests, bool $beStrictAboutTestsThatDoNotTestAnything, bool $beStrictAboutCoverageMetadata, bool $enforceTimeLimit, int $defaultTimeLimit, int $timeoutForSmallTests, int $timeoutForMediumTests, int $timeoutForLargeTests, ?string $defaultTestSuite, int $executionOrder, bool $resolveDependencies, bool $defectsFirst, bool $backupGlobals, bool $backupStaticProperties, bool $registerMockObjectsFromTestArgumentsRecursively, bool $testdoxPrinter, bool $controlGarbageCollector, int $numberOfTestsBeforeGarbageCollection)
    {
        $this->cacheDirectory                                  = $cacheDirectory;
        $this->cacheResult                                     = $cacheResult;
        $this->cacheResultFile                                 = $cacheResultFile;
        $this->columns                                         = $columns;
        $this->colors                                          = $colors;
        $this->stderr                                          = $stderr;
        $this->displayDetailsOnAllIssues                       = $displayDetailsOnAllIssues;
        $this->displayDetailsOnIncompleteTests                 = $displayDetailsOnIncompleteTests;
        $this->displayDetailsOnSkippedTests                    = $displayDetailsOnSkippedTests;
        $this->displayDetailsOnTestsThatTriggerDeprecations    = $displayDetailsOnTestsThatTriggerDeprecations;
        $this->displayDetailsOnPhpunitDeprecations             = $displayDetailsOnPhpunitDeprecations;
        $this->displayDetailsOnTestsThatTriggerErrors          = $displayDetailsOnTestsThatTriggerErrors;
        $this->displayDetailsOnTestsThatTriggerNotices         = $displayDetailsOnTestsThatTriggerNotices;
        $this->displayDetailsOnTestsThatTriggerWarnings        = $displayDetailsOnTestsThatTriggerWarnings;
        $this->reverseDefectList                               = $reverseDefectList;
        $this->requireCoverageMetadata                         = $requireCoverageMetadata;
        $this->bootstrap                                       = $bootstrap;
        $this->processIsolation                                = $processIsolation;
        $this->failOnAllIssues                                 = $failOnAllIssues;
        $this->failOnDeprecation                               = $failOnDeprecation;
        $this->failOnPhpunitDeprecation                        = $failOnPhpunitDeprecation;
        $this->failOnPhpunitWarning                            = $failOnPhpunitWarning;
        $this->failOnEmptyTestSuite                            = $failOnEmptyTestSuite;
        $this->failOnIncomplete                                = $failOnIncomplete;
        $this->failOnNotice                                    = $failOnNotice;
        $this->failOnRisky                                     = $failOnRisky;
        $this->failOnSkipped                                   = $failOnSkipped;
        $this->failOnWarning                                   = $failOnWarning;
        $this->stopOnDefect                                    = $stopOnDefect;
        $this->stopOnDeprecation                               = $stopOnDeprecation;
        $this->stopOnError                                     = $stopOnError;
        $this->stopOnFailure                                   = $stopOnFailure;
        $this->stopOnIncomplete                                = $stopOnIncomplete;
        $this->stopOnNotice                                    = $stopOnNotice;
        $this->stopOnRisky                                     = $stopOnRisky;
        $this->stopOnSkipped                                   = $stopOnSkipped;
        $this->stopOnWarning                                   = $stopOnWarning;
        $this->extensionsDirectory                             = $extensionsDirectory;
        $this->beStrictAboutChangesToGlobalState               = $beStrictAboutChangesToGlobalState;
        $this->beStrictAboutOutputDuringTests                  = $beStrictAboutOutputDuringTests;
        $this->beStrictAboutTestsThatDoNotTestAnything         = $beStrictAboutTestsThatDoNotTestAnything;
        $this->beStrictAboutCoverageMetadata                   = $beStrictAboutCoverageMetadata;
        $this->enforceTimeLimit                                = $enforceTimeLimit;
        $this->defaultTimeLimit                                = $defaultTimeLimit;
        $this->timeoutForSmallTests                            = $timeoutForSmallTests;
        $this->timeoutForMediumTests                           = $timeoutForMediumTests;
        $this->timeoutForLargeTests                            = $timeoutForLargeTests;
        $this->defaultTestSuite                                = $defaultTestSuite;
        $this->executionOrder                                  = $executionOrder;
        $this->resolveDependencies                             = $resolveDependencies;
        $this->defectsFirst                                    = $defectsFirst;
        $this->backupGlobals                                   = $backupGlobals;
        $this->backupStaticProperties                          = $backupStaticProperties;
        $this->registerMockObjectsFromTestArgumentsRecursively = $registerMockObjectsFromTestArgumentsRecursively;
        $this->testdoxPrinter                                  = $testdoxPrinter;
        $this->controlGarbageCollector                         = $controlGarbageCollector;
        $this->numberOfTestsBeforeGarbageCollection            = $numberOfTestsBeforeGarbageCollection;
    }

    /**
     * @psalm-assert-if-true !null $this->cacheDirectory
     */
    public function hasCacheDirectory(): bool
    {
        return $this->cacheDirectory !== null;
    }

    /**
     * @throws Exception
     */
    public function cacheDirectory(): string
    {
        if (!$this->hasCacheDirectory()) {
            throw new Exception('Cache directory is not configured');
        }

        return $this->cacheDirectory;
    }

    public function cacheResult(): bool
    {
        return $this->cacheResult;
    }

    /**
     * @psalm-assert-if-true !null $this->cacheResultFile
     *
     * @deprecated
     */
    public function hasCacheResultFile(): bool
    {
        return $this->cacheResultFile !== null;
    }

    /**
     * @throws Exception
     *
     * @deprecated
     */
    public function cacheResultFile(): string
    {
        if (!$this->hasCacheResultFile()) {
            throw new Exception('Cache result file is not configured');
        }

        return $this->cacheResultFile;
    }

    public function columns(): int|string
    {
        return $this->columns;
    }

    public function colors(): string
    {
        return $this->colors;
    }

    public function stderr(): bool
    {
        return $this->stderr;
    }

    public function displayDetailsOnAllIssues(): bool
    {
        return $this->displayDetailsOnAllIssues;
    }

    public function displayDetailsOnIncompleteTests(): bool
    {
        return $this->displayDetailsOnIncompleteTests;
    }

    public function displayDetailsOnSkippedTests(): bool
    {
        return $this->displayDetailsOnSkippedTests;
    }

    public function displayDetailsOnTestsThatTriggerDeprecations(): bool
    {
        return $this->displayDetailsOnTestsThatTriggerDeprecations;
    }

    public function displayDetailsOnPhpunitDeprecations(): bool
    {
        return $this->displayDetailsOnPhpunitDeprecations;
    }

    public function displayDetailsOnTestsThatTriggerErrors(): bool
    {
        return $this->displayDetailsOnTestsThatTriggerErrors;
    }

    public function displayDetailsOnTestsThatTriggerNotices(): bool
    {
        return $this->displayDetailsOnTestsThatTriggerNotices;
    }

    public function displayDetailsOnTestsThatTriggerWarnings(): bool
    {
        return $this->displayDetailsOnTestsThatTriggerWarnings;
    }

    public function reverseDefectList(): bool
    {
        return $this->reverseDefectList;
    }

    public function requireCoverageMetadata(): bool
    {
        return $this->requireCoverageMetadata;
    }

    /**
     * @psalm-assert-if-true !null $this->bootstrap
     */
    public function hasBootstrap(): bool
    {
        return $this->bootstrap !== null;
    }

    /**
     * @throws Exception
     */
    public function bootstrap(): string
    {
        if (!$this->hasBootstrap()) {
            throw new Exception('Bootstrap script is not configured');
        }

        return $this->bootstrap;
    }

    public function processIsolation(): bool
    {
        return $this->processIsolation;
    }

    public function failOnAllIssues(): bool
    {
        return $this->failOnAllIssues;
    }

    public function failOnDeprecation(): bool
    {
        return $this->failOnDeprecation;
    }

    public function failOnPhpunitDeprecation(): bool
    {
        return $this->failOnPhpunitDeprecation;
    }

    public function failOnPhpunitWarning(): bool
    {
        return $this->failOnPhpunitWarning;
    }

    public function failOnEmptyTestSuite(): bool
    {
        return $this->failOnEmptyTestSuite;
    }

    public function failOnIncomplete(): bool
    {
        return $this->failOnIncomplete;
    }

    public function failOnNotice(): bool
    {
        return $this->failOnNotice;
    }

    public function failOnRisky(): bool
    {
        return $this->failOnRisky;
    }

    public function failOnSkipped(): bool
    {
        return $this->failOnSkipped;
    }

    public function failOnWarning(): bool
    {
        return $this->failOnWarning;
    }

    public function stopOnDefect(): bool
    {
        return $this->stopOnDefect;
    }

    public function stopOnDeprecation(): bool
    {
        return $this->stopOnDeprecation;
    }

    public function stopOnError(): bool
    {
        return $this->stopOnError;
    }

    public function stopOnFailure(): bool
    {
        return $this->stopOnFailure;
    }

    public function stopOnIncomplete(): bool
    {
        return $this->stopOnIncomplete;
    }

    public function stopOnNotice(): bool
    {
        return $this->stopOnNotice;
    }

    public function stopOnRisky(): bool
    {
        return $this->stopOnRisky;
    }

    public function stopOnSkipped(): bool
    {
        return $this->stopOnSkipped;
    }

    public function stopOnWarning(): bool
    {
        return $this->stopOnWarning;
    }

    /**
     * @psalm-assert-if-true !null $this->extensionsDirectory
     */
    public function hasExtensionsDirectory(): bool
    {
        return $this->extensionsDirectory !== null;
    }

    /**
     * @throws Exception
     *
     * @psalm-return non-empty-string
     */
    public function extensionsDirectory(): string
    {
        if (!$this->hasExtensionsDirectory()) {
            throw new Exception('Extensions directory is not configured');
        }

        return $this->extensionsDirectory;
    }

    public function beStrictAboutChangesToGlobalState(): bool
    {
        return $this->beStrictAboutChangesToGlobalState;
    }

    public function beStrictAboutOutputDuringTests(): bool
    {
        return $this->beStrictAboutOutputDuringTests;
    }

    public function beStrictAboutTestsThatDoNotTestAnything(): bool
    {
        return $this->beStrictAboutTestsThatDoNotTestAnything;
    }

    public function beStrictAboutCoverageMetadata(): bool
    {
        return $this->beStrictAboutCoverageMetadata;
    }

    public function enforceTimeLimit(): bool
    {
        return $this->enforceTimeLimit;
    }

    public function defaultTimeLimit(): int
    {
        return $this->defaultTimeLimit;
    }

    public function timeoutForSmallTests(): int
    {
        return $this->timeoutForSmallTests;
    }

    public function timeoutForMediumTests(): int
    {
        return $this->timeoutForMediumTests;
    }

    public function timeoutForLargeTests(): int
    {
        return $this->timeoutForLargeTests;
    }

    /**
     * @psalm-assert-if-true !null $this->defaultTestSuite
     */
    public function hasDefaultTestSuite(): bool
    {
        return $this->defaultTestSuite !== null;
    }

    /**
     * @throws Exception
     */
    public function defaultTestSuite(): string
    {
        if (!$this->hasDefaultTestSuite()) {
            throw new Exception('Default test suite is not configured');
        }

        return $this->defaultTestSuite;
    }

    public function executionOrder(): int
    {
        return $this->executionOrder;
    }

    public function resolveDependencies(): bool
    {
        return $this->resolveDependencies;
    }

    public function defectsFirst(): bool
    {
        return $this->defectsFirst;
    }

    public function backupGlobals(): bool
    {
        return $this->backupGlobals;
    }

    public function backupStaticProperties(): bool
    {
        return $this->backupStaticProperties;
    }

    /**
     * @deprecated
     */
    public function registerMockObjectsFromTestArgumentsRecursively(): bool
    {
        return $this->registerMockObjectsFromTestArgumentsRecursively;
    }

    public function testdoxPrinter(): bool
    {
        return $this->testdoxPrinter;
    }

    public function controlGarbageCollector(): bool
    {
        return $this->controlGarbageCollector;
    }

    public function numberOfTestsBeforeGarbageCollection(): int
    {
        return $this->numberOfTestsBeforeGarbageCollection;
    }
}
