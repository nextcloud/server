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

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DIRECTORY_SEPARATOR;
use function array_merge;
use function basename;
use function debug_backtrace;
use function defined;
use function dirname;
use function explode;
use function extension_loaded;
use function file;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_file;
use function is_readable;
use function is_string;
use function ltrim;
use function preg_match;
use function preg_replace;
use function preg_split;
use function realpath;
use function rtrim;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strncasecmp;
use function substr;
use function trim;
use function unlink;
use function unserialize;
use function var_export;
use PHPUnit\Event\Code\Phpt;
use PHPUnit\Event\Code\ThrowableBuilder;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Event\NoPreviousThrowableException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExecutionOrderDependency;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\Framework\PhptAssertionFailedError;
use PHPUnit\Framework\Reorderable;
use PHPUnit\Framework\SelfDescribing;
use PHPUnit\Framework\Test;
use PHPUnit\TextUI\Configuration\Registry as ConfigurationRegistry;
use PHPUnit\Util\PHP\AbstractPhpProcess;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\InvalidArgumentException;
use SebastianBergmann\CodeCoverage\ReflectionException;
use SebastianBergmann\CodeCoverage\Test\TestSize\TestSize;
use SebastianBergmann\CodeCoverage\Test\TestStatus\TestStatus;
use SebastianBergmann\CodeCoverage\TestIdMissingException;
use SebastianBergmann\CodeCoverage\UnintentionallyCoveredCodeException;
use SebastianBergmann\Template\Template;
use Throwable;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class PhptTestCase implements Reorderable, SelfDescribing, Test
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $filename;
    private readonly AbstractPhpProcess $phpUtil;
    private string $output = '';

    /**
     * Constructs a test case with the given filename.
     *
     * @psalm-param non-empty-string $filename
     *
     * @throws Exception
     */
    public function __construct(string $filename, ?AbstractPhpProcess $phpUtil = null)
    {
        $this->filename = $filename;
        $this->phpUtil  = $phpUtil ?: AbstractPhpProcess::factory();
    }

    /**
     * Counts the number of test cases executed by run(TestResult result).
     */
    public function count(): int
    {
        return 1;
    }

    /**
     * Runs a test and collects its result in a TestResult instance.
     *
     * @throws \PHPUnit\Framework\Exception
     * @throws \SebastianBergmann\Template\InvalidArgumentException
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NoPreviousThrowableException
     * @throws ReflectionException
     * @throws TestIdMissingException
     * @throws UnintentionallyCoveredCodeException
     *
     * @noinspection RepetitiveMethodCallsInspection
     */
    public function run(): void
    {
        $emitter = EventFacade::emitter();

        $emitter->testPreparationStarted(
            $this->valueObjectForEvents(),
        );

        try {
            $sections = $this->parse();
        } catch (Exception $e) {
            $emitter->testPrepared($this->valueObjectForEvents());
            $emitter->testErrored($this->valueObjectForEvents(), ThrowableBuilder::from($e));
            $emitter->testFinished($this->valueObjectForEvents(), 0);

            return;
        }

        $code     = $this->render($sections['FILE']);
        $xfail    = false;
        $settings = $this->parseIniSection($this->settings(CodeCoverage::instance()->isActive()));

        $emitter->testPrepared($this->valueObjectForEvents());

        if (isset($sections['INI'])) {
            $settings = $this->parseIniSection($sections['INI'], $settings);
        }

        if (isset($sections['ENV'])) {
            $env = $this->parseEnvSection($sections['ENV']);
            $this->phpUtil->setEnv($env);
        }

        $this->phpUtil->setUseStderrRedirection(true);

        if ($this->shouldTestBeSkipped($sections, $settings)) {
            return;
        }

        if (isset($sections['XFAIL'])) {
            $xfail = trim($sections['XFAIL']);
        }

        if (isset($sections['STDIN'])) {
            $this->phpUtil->setStdin($sections['STDIN']);
        }

        if (isset($sections['ARGS'])) {
            $this->phpUtil->setArgs($sections['ARGS']);
        }

        if (CodeCoverage::instance()->isActive()) {
            $codeCoverageCacheDirectory = null;

            if (CodeCoverage::instance()->codeCoverage()->cachesStaticAnalysis()) {
                /** @psalm-suppress MissingThrowsDocblock */
                $codeCoverageCacheDirectory = CodeCoverage::instance()->codeCoverage()->cacheDirectory();
            }

            $this->renderForCoverage(
                $code,
                CodeCoverage::instance()->codeCoverage()->collectsBranchAndPathCoverage(),
                $codeCoverageCacheDirectory,
            );
        }

        $jobResult    = $this->phpUtil->runJob($code, $this->stringifyIni($settings));
        $this->output = $jobResult['stdout'] ?? '';

        if (CodeCoverage::instance()->isActive()) {
            $coverage = $this->cleanupForCoverage();

            CodeCoverage::instance()->codeCoverage()->start($this->filename, TestSize::large());

            CodeCoverage::instance()->codeCoverage()->append(
                $coverage,
                $this->filename,
                true,
                TestStatus::unknown(),
            );
        }

        $passed = true;

        try {
            $this->assertPhptExpectation($sections, $this->output);
        } catch (AssertionFailedError $e) {
            $failure = $e;

            if ($xfail !== false) {
                $failure = new IncompleteTestError($xfail, 0, $e);
            } elseif ($e instanceof ExpectationFailedException) {
                $comparisonFailure = $e->getComparisonFailure();

                if ($comparisonFailure) {
                    $diff = $comparisonFailure->getDiff();
                } else {
                    $diff = $e->getMessage();
                }

                $hint    = $this->getLocationHintFromDiff($diff, $sections);
                $trace   = array_merge($hint, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
                $failure = new PhptAssertionFailedError(
                    $e->getMessage(),
                    0,
                    (string) $trace[0]['file'],
                    (int) $trace[0]['line'],
                    $trace,
                    $comparisonFailure ? $diff : '',
                );
            }

            if ($failure instanceof IncompleteTestError) {
                $emitter->testMarkedAsIncomplete($this->valueObjectForEvents(), ThrowableBuilder::from($failure));
            } else {
                $emitter->testFailed($this->valueObjectForEvents(), ThrowableBuilder::from($failure), null);
            }

            $passed = false;
        } catch (Throwable $t) {
            $emitter->testErrored($this->valueObjectForEvents(), ThrowableBuilder::from($t));

            $passed = false;
        }

        if ($passed) {
            $emitter->testPassed($this->valueObjectForEvents());
        }

        $this->runClean($sections, CodeCoverage::instance()->isActive());

        $emitter->testFinished($this->valueObjectForEvents(), 1);
    }

    /**
     * Returns the name of the test case.
     */
    public function getName(): string
    {
        return $this->toString();
    }

    /**
     * Returns a string representation of the test case.
     */
    public function toString(): string
    {
        return $this->filename;
    }

    public function usesDataProvider(): bool
    {
        return false;
    }

    public function numberOfAssertionsPerformed(): int
    {
        return 1;
    }

    public function output(): string
    {
        return $this->output;
    }

    public function hasOutput(): bool
    {
        return !empty($this->output);
    }

    public function sortId(): string
    {
        return $this->filename;
    }

    /**
     * @psalm-return list<ExecutionOrderDependency>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * @psalm-return list<ExecutionOrderDependency>
     */
    public function requires(): array
    {
        return [];
    }

    /**
     * @internal This method is not covered by the backward compatibility promise for PHPUnit
     */
    public function valueObjectForEvents(): Phpt
    {
        return new Phpt($this->filename);
    }

    /**
     * Parse --INI-- section key value pairs and return as array.
     */
    private function parseIniSection(array|string $content, array $ini = []): array
    {
        if (is_string($content)) {
            $content = explode("\n", trim($content));
        }

        foreach ($content as $setting) {
            if (!str_contains($setting, '=')) {
                continue;
            }

            $setting = explode('=', $setting, 2);
            $name    = trim($setting[0]);
            $value   = trim($setting[1]);

            if ($name === 'extension' || $name === 'zend_extension') {
                if (!isset($ini[$name])) {
                    $ini[$name] = [];
                }

                $ini[$name][] = $value;

                continue;
            }

            $ini[$name] = $value;
        }

        return $ini;
    }

    private function parseEnvSection(string $content): array
    {
        $env = [];

        foreach (explode("\n", trim($content)) as $e) {
            $e = explode('=', trim($e), 2);

            if ($e[0] !== '' && isset($e[1])) {
                $env[$e[0]] = $e[1];
            }
        }

        return $env;
    }

    /**
     * @throws Exception
     * @throws ExpectationFailedException
     */
    private function assertPhptExpectation(array $sections, string $output): void
    {
        $assertions = [
            'EXPECT'      => 'assertEquals',
            'EXPECTF'     => 'assertStringMatchesFormat',
            'EXPECTREGEX' => 'assertMatchesRegularExpression',
        ];

        $actual = preg_replace('/\r\n/', "\n", trim($output));

        foreach ($assertions as $sectionName => $sectionAssertion) {
            if (isset($sections[$sectionName])) {
                $sectionContent = preg_replace('/\r\n/', "\n", trim($sections[$sectionName]));
                $expected       = $sectionName === 'EXPECTREGEX' ? "/{$sectionContent}/" : $sectionContent;

                Assert::$sectionAssertion($expected, $actual);

                return;
            }
        }

        throw new InvalidPhptFileException;
    }

    private function shouldTestBeSkipped(array $sections, array $settings): bool
    {
        if (!isset($sections['SKIPIF'])) {
            return false;
        }

        $skipif    = $this->render($sections['SKIPIF']);
        $jobResult = $this->phpUtil->runJob($skipif, $this->stringifyIni($settings));

        if (!strncasecmp('skip', ltrim($jobResult['stdout']), 4)) {
            $message = '';

            if (preg_match('/^\s*skip\s*(.+)\s*/i', $jobResult['stdout'], $skipMatch)) {
                $message = substr($skipMatch[1], 2);
            }

            EventFacade::emitter()->testSkipped(
                $this->valueObjectForEvents(),
                $message,
            );

            EventFacade::emitter()->testFinished($this->valueObjectForEvents(), 0);

            return true;
        }

        return false;
    }

    private function runClean(array $sections, bool $collectCoverage): void
    {
        $this->phpUtil->setStdin('');
        $this->phpUtil->setArgs('');

        if (isset($sections['CLEAN'])) {
            $cleanCode = $this->render($sections['CLEAN']);

            $this->phpUtil->runJob($cleanCode, $this->settings($collectCoverage));
        }
    }

    /**
     * @throws Exception
     */
    private function parse(): array
    {
        $sections = [];
        $section  = '';

        $unsupportedSections = [
            'CGI',
            'COOKIE',
            'DEFLATE_POST',
            'EXPECTHEADERS',
            'EXTENSIONS',
            'GET',
            'GZIP_POST',
            'HEADERS',
            'PHPDBG',
            'POST',
            'POST_RAW',
            'PUT',
            'REDIRECTTEST',
            'REQUEST',
        ];

        $lineNr = 0;

        foreach (file($this->filename) as $line) {
            $lineNr++;

            if (preg_match('/^--([_A-Z]+)--/', $line, $result)) {
                $section                        = $result[1];
                $sections[$section]             = '';
                $sections[$section . '_offset'] = $lineNr;

                continue;
            }

            if (empty($section)) {
                throw new InvalidPhptFileException;
            }

            $sections[$section] .= $line;
        }

        if (isset($sections['FILEEOF'])) {
            $sections['FILE'] = rtrim($sections['FILEEOF'], "\r\n");
            unset($sections['FILEEOF']);
        }

        $this->parseExternal($sections);

        if (!$this->validate($sections)) {
            throw new InvalidPhptFileException;
        }

        foreach ($unsupportedSections as $section) {
            if (isset($sections[$section])) {
                throw new UnsupportedPhptSectionException($section);
            }
        }

        return $sections;
    }

    /**
     * @throws Exception
     */
    private function parseExternal(array &$sections): void
    {
        $allowSections = [
            'FILE',
            'EXPECT',
            'EXPECTF',
            'EXPECTREGEX',
        ];
        $testDirectory = dirname($this->filename) . DIRECTORY_SEPARATOR;

        foreach ($allowSections as $section) {
            if (isset($sections[$section . '_EXTERNAL'])) {
                $externalFilename = trim($sections[$section . '_EXTERNAL']);

                if (!is_file($testDirectory . $externalFilename) ||
                    !is_readable($testDirectory . $externalFilename)) {
                    throw new PhptExternalFileCannotBeLoadedException(
                        $section,
                        $testDirectory . $externalFilename,
                    );
                }

                $sections[$section] = file_get_contents($testDirectory . $externalFilename);
            }
        }
    }

    private function validate(array $sections): bool
    {
        $requiredSections = [
            'FILE',
            [
                'EXPECT',
                'EXPECTF',
                'EXPECTREGEX',
            ],
        ];

        foreach ($requiredSections as $section) {
            if (is_array($section)) {
                $foundSection = false;

                foreach ($section as $anySection) {
                    if (isset($sections[$anySection])) {
                        $foundSection = true;

                        break;
                    }
                }

                if (!$foundSection) {
                    return false;
                }

                continue;
            }

            if (!isset($sections[$section])) {
                return false;
            }
        }

        return true;
    }

    private function render(string $code): string
    {
        return str_replace(
            [
                '__DIR__',
                '__FILE__',
            ],
            [
                "'" . dirname($this->filename) . "'",
                "'" . $this->filename . "'",
            ],
            $code,
        );
    }

    private function getCoverageFiles(): array
    {
        $baseDir  = dirname(realpath($this->filename)) . DIRECTORY_SEPARATOR;
        $basename = basename($this->filename, 'phpt');

        return [
            'coverage' => $baseDir . $basename . 'coverage',
            'job'      => $baseDir . $basename . 'php',
        ];
    }

    /**
     * @throws \SebastianBergmann\Template\InvalidArgumentException
     */
    private function renderForCoverage(string &$job, bool $pathCoverage, ?string $codeCoverageCacheDirectory): void
    {
        $files = $this->getCoverageFiles();

        $template = new Template(
            __DIR__ . '/../Util/PHP/Template/PhptTestCase.tpl',
        );

        $composerAutoload = '\'\'';

        if (defined('PHPUNIT_COMPOSER_INSTALL')) {
            $composerAutoload = var_export(PHPUNIT_COMPOSER_INSTALL, true);
        }

        $phar = '\'\'';

        if (defined('__PHPUNIT_PHAR__')) {
            $phar = var_export(__PHPUNIT_PHAR__, true);
        }

        if ($codeCoverageCacheDirectory === null) {
            $codeCoverageCacheDirectory = 'null';
        } else {
            $codeCoverageCacheDirectory = "'" . $codeCoverageCacheDirectory . "'";
        }

        $bootstrap = '';

        if (ConfigurationRegistry::get()->hasBootstrap()) {
            $bootstrap = ConfigurationRegistry::get()->bootstrap();
        }

        $template->setVar(
            [
                'bootstrap'                  => $bootstrap,
                'composerAutoload'           => $composerAutoload,
                'phar'                       => $phar,
                'job'                        => $files['job'],
                'coverageFile'               => $files['coverage'],
                'driverMethod'               => $pathCoverage ? 'forLineAndPathCoverage' : 'forLineCoverage',
                'codeCoverageCacheDirectory' => $codeCoverageCacheDirectory,
            ],
        );

        file_put_contents($files['job'], $job);

        $job = $template->render();
    }

    private function cleanupForCoverage(): RawCodeCoverageData
    {
        $coverage = RawCodeCoverageData::fromXdebugWithoutPathCoverage([]);
        $files    = $this->getCoverageFiles();

        $buffer = false;

        if (is_file($files['coverage'])) {
            $buffer = @file_get_contents($files['coverage']);
        }

        if ($buffer !== false) {
            $coverage = @unserialize($buffer);

            if ($coverage === false) {
                $coverage = RawCodeCoverageData::fromXdebugWithoutPathCoverage([]);
            }
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return $coverage;
    }

    private function stringifyIni(array $ini): array
    {
        $settings = [];

        foreach ($ini as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $settings[] = $key . '=' . $val;
                }

                continue;
            }

            $settings[] = $key . '=' . $value;
        }

        return $settings;
    }

    private function getLocationHintFromDiff(string $message, array $sections): array
    {
        $needle       = '';
        $previousLine = '';
        $block        = 'message';

        foreach (preg_split('/\r\n|\r|\n/', $message) as $line) {
            $line = trim($line);

            if ($block === 'message' && $line === '--- Expected') {
                $block = 'expected';
            }

            if ($block === 'expected' && $line === '@@ @@') {
                $block = 'diff';
            }

            if ($block === 'diff') {
                if (str_starts_with($line, '+')) {
                    $needle = $this->getCleanDiffLine($previousLine);

                    break;
                }

                if (str_starts_with($line, '-')) {
                    $needle = $this->getCleanDiffLine($line);

                    break;
                }
            }

            if (!empty($line)) {
                $previousLine = $line;
            }
        }

        return $this->getLocationHint($needle, $sections);
    }

    private function getCleanDiffLine(string $line): string
    {
        if (preg_match('/^[\-+]([\'\"]?)(.*)\1$/', $line, $matches)) {
            $line = $matches[2];
        }

        return $line;
    }

    private function getLocationHint(string $needle, array $sections): array
    {
        $needle = trim($needle);

        if (empty($needle)) {
            return [[
                'file' => realpath($this->filename),
                'line' => 1,
            ]];
        }

        $search = [
            // 'FILE',
            'EXPECT',
            'EXPECTF',
            'EXPECTREGEX',
        ];

        foreach ($search as $section) {
            if (!isset($sections[$section])) {
                continue;
            }

            if (isset($sections[$section . '_EXTERNAL'])) {
                $externalFile = trim($sections[$section . '_EXTERNAL']);

                return [
                    [
                        'file' => realpath(dirname($this->filename) . DIRECTORY_SEPARATOR . $externalFile),
                        'line' => 1,
                    ],
                    [
                        'file' => realpath($this->filename),
                        'line' => ($sections[$section . '_EXTERNAL_offset'] ?? 0) + 1,
                    ],
                ];
            }

            $sectionOffset = $sections[$section . '_offset'] ?? 0;
            $offset        = $sectionOffset + 1;

            foreach (preg_split('/\r\n|\r|\n/', $sections[$section]) as $line) {
                if (str_contains($line, $needle)) {
                    return [
                        [
                            'file' => realpath($this->filename),
                            'line' => $offset,
                        ],
                    ];
                }

                $offset++;
            }
        }

        return [
            [
                'file' => realpath($this->filename),
                'line' => 1,
            ],
        ];
    }

    /**
     * @psalm-return list<string>
     */
    private function settings(bool $collectCoverage): array
    {
        $settings = [
            'allow_url_fopen=1',
            'auto_append_file=',
            'auto_prepend_file=',
            'disable_functions=',
            'display_errors=1',
            'docref_ext=.html',
            'docref_root=',
            'error_append_string=',
            'error_prepend_string=',
            'error_reporting=-1',
            'html_errors=0',
            'log_errors=0',
            'open_basedir=',
            'output_buffering=Off',
            'output_handler=',
            'report_memleaks=0',
            'report_zend_debug=0',
        ];

        if (extension_loaded('pcov')) {
            if ($collectCoverage) {
                $settings[] = 'pcov.enabled=1';
            } else {
                $settings[] = 'pcov.enabled=0';
            }
        }

        if (extension_loaded('xdebug')) {
            if ($collectCoverage) {
                $settings[] = 'xdebug.mode=coverage';
            } else {
                $settings[] = 'xdebug.mode=off';
            }
        }

        return $settings;
    }
}
