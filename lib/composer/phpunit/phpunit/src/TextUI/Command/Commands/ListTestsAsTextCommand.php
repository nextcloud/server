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
use function str_replace;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\PhptTestCase;
use PHPUnit\TextUI\Configuration\Registry;
use RecursiveIteratorIterator;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class ListTestsAsTextCommand implements Command
{
    private readonly TestSuite $suite;

    public function __construct(TestSuite $suite)
    {
        $this->suite = $suite;
    }

    public function execute(): Result
    {
        $buffer = $this->warnAboutConflictingOptions();

        $buffer .= 'Available test(s):' . PHP_EOL;

        foreach (new RecursiveIteratorIterator($this->suite) as $test) {
            if ($test instanceof TestCase) {
                $name = sprintf(
                    '%s::%s',
                    $test::class,
                    str_replace(' with data set ', '', $test->nameWithDataSet()),
                );
            } elseif ($test instanceof PhptTestCase) {
                $name = $test->getName();
            } else {
                continue;
            }

            $buffer .= sprintf(
                ' - %s' . PHP_EOL,
                $name,
            );
        }

        return Result::from($buffer);
    }

    private function warnAboutConflictingOptions(): string
    {
        $buffer = '';

        $configuration = Registry::get();

        if ($configuration->hasFilter()) {
            $buffer .= 'The --filter and --list-tests options cannot be combined, --filter is ignored' . PHP_EOL;
        }

        if ($configuration->hasGroups()) {
            $buffer .= 'The --group and --list-tests options cannot be combined, --group is ignored' . PHP_EOL;
        }

        if ($configuration->hasExcludeGroups()) {
            $buffer .= 'The --exclude-group and --list-tests options cannot be combined, --exclude-group is ignored' . PHP_EOL;
        }

        if (!empty($buffer)) {
            $buffer .= PHP_EOL;
        }

        return $buffer;
    }
}
