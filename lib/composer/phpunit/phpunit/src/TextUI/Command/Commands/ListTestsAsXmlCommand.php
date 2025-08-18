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
use function file_put_contents;
use function implode;
use function sprintf;
use function str_replace;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\PhptTestCase;
use PHPUnit\TextUI\Configuration\Registry;
use RecursiveIteratorIterator;
use XMLWriter;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class ListTestsAsXmlCommand implements Command
{
    private readonly string $filename;
    private readonly TestSuite $suite;

    public function __construct(string $filename, TestSuite $suite)
    {
        $this->filename = $filename;
        $this->suite    = $suite;
    }

    public function execute(): Result
    {
        $buffer = $this->warnAboutConflictingOptions();
        $writer = new XMLWriter;

        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument();
        $writer->startElement('tests');

        $currentTestCase = null;

        foreach (new RecursiveIteratorIterator($this->suite) as $test) {
            if ($test instanceof TestCase) {
                if ($test::class !== $currentTestCase) {
                    if ($currentTestCase !== null) {
                        $writer->endElement();
                    }

                    $writer->startElement('testCaseClass');
                    $writer->writeAttribute('name', $test::class);

                    $currentTestCase = $test::class;
                }

                $writer->startElement('testCaseMethod');
                $writer->writeAttribute('id', $test->valueObjectForEvents()->id());
                $writer->writeAttribute('name', $test->name());
                $writer->writeAttribute('groups', implode(',', $test->groups()));

                /**
                 * @deprecated https://github.com/sebastianbergmann/phpunit/issues/5481
                 */
                if (!empty($test->dataSetAsString())) {
                    $writer->writeAttribute(
                        'dataSet',
                        str_replace(
                            ' with data set ',
                            '',
                            $test->dataSetAsString(),
                        ),
                    );
                }

                $writer->endElement();

                continue;
            }

            if ($test instanceof PhptTestCase) {
                if ($currentTestCase !== null) {
                    $writer->endElement();

                    $currentTestCase = null;
                }

                $writer->startElement('phptFile');
                $writer->writeAttribute('path', $test->getName());
                $writer->endElement();
            }
        }

        if ($currentTestCase !== null) {
            $writer->endElement();
        }

        $writer->endElement();

        file_put_contents($this->filename, $writer->outputMemory());

        $buffer .= sprintf(
            'Wrote list of tests that would have been run to %s' . PHP_EOL,
            $this->filename,
        );

        return Result::from($buffer);
    }

    private function warnAboutConflictingOptions(): string
    {
        $buffer = '';

        $configuration = Registry::get();

        if ($configuration->hasFilter()) {
            $buffer .= 'The --filter and --list-tests-xml options cannot be combined, --filter is ignored' . PHP_EOL;
        }

        if ($configuration->hasGroups()) {
            $buffer .= 'The --group and --list-tests-xml options cannot be combined, --group is ignored' . PHP_EOL;
        }

        if ($configuration->hasExcludeGroups()) {
            $buffer .= 'The --exclude-group and --list-tests-xml options cannot be combined, --exclude-group is ignored' . PHP_EOL;
        }

        if (!empty($buffer)) {
            $buffer .= PHP_EOL;
        }

        return $buffer;
    }
}
