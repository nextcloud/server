<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\Command;

use const PHP_EOL;
use function sprintf;
use PHPUnit\TextUI\Configuration\Registry;
use PHPUnit\TextUI\Configuration\TestSuiteCollection;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class ListTestSuitesCommand implements Command
{
    private readonly TestSuiteCollection $suites;

    public function __construct(TestSuiteCollection $suites)
    {
        $this->suites = $suites;
    }

    public function execute(): Result
    {
        $buffer = $this->warnAboutConflictingOptions();
        $buffer .= 'Available test suite(s):' . PHP_EOL;

        foreach ($this->suites as $suite) {
            $buffer .= sprintf(
                ' - %s' . PHP_EOL,
                $suite->name(),
            );
        }

        return Result::from($buffer);
    }

    private function warnAboutConflictingOptions(): string
    {
        $buffer = '';

        $configuration = Registry::get();

        if ($configuration->hasFilter()) {
            $buffer .= 'The --filter and --list-suites options cannot be combined, --filter is ignored' . PHP_EOL;
        }

        if ($configuration->hasGroups()) {
            $buffer .= 'The --group and --list-suites options cannot be combined, --group is ignored' . PHP_EOL;
        }

        if ($configuration->hasExcludeGroups()) {
            $buffer .= 'The --exclude-group and --list-suites options cannot be combined, --exclude-group is ignored' . PHP_EOL;
        }

        if ($configuration->includeTestSuite() !== '') {
            $buffer .= 'The --testsuite and --list-suites options cannot be combined, --testsuite is ignored' . PHP_EOL;
        }

        if (!empty($buffer)) {
            $buffer .= PHP_EOL;
        }

        return $buffer;
    }
}
