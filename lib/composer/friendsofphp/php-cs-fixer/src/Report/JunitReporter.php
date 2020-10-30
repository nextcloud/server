<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Report;

use PhpCsFixer\Preg;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * @author Boris Gorbylev <ekho@ekho.name>
 *
 * @internal
 */
final class JunitReporter implements ReporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFormat()
    {
        return 'junit';
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ReportSummary $reportSummary)
    {
        if (!\extension_loaded('dom')) {
            throw new \RuntimeException('Cannot generate report! `ext-dom` is not available!');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $testsuites = $dom->appendChild($dom->createElement('testsuites'));
        /** @var \DomElement $testsuite */
        $testsuite = $testsuites->appendChild($dom->createElement('testsuite'));
        $testsuite->setAttribute('name', 'PHP CS Fixer');

        if (\count($reportSummary->getChanged())) {
            $this->createFailedTestCases($dom, $testsuite, $reportSummary);
        } else {
            $this->createSuccessTestCase($dom, $testsuite);
        }

        if ($reportSummary->getTime()) {
            $testsuite->setAttribute(
                'time',
                sprintf(
                    '%.3f',
                    $reportSummary->getTime() / 1000
                )
            );
        }

        $dom->formatOutput = true;

        return $reportSummary->isDecoratedOutput() ? OutputFormatter::escape($dom->saveXML()) : $dom->saveXML();
    }

    private function createSuccessTestCase(\DOMDocument $dom, \DOMElement $testsuite)
    {
        $testcase = $dom->createElement('testcase');
        $testcase->setAttribute('name', 'All OK');
        $testcase->setAttribute('assertions', '1');

        $testsuite->appendChild($testcase);
        $testsuite->setAttribute('tests', '1');
        $testsuite->setAttribute('assertions', '1');
        $testsuite->setAttribute('failures', '0');
        $testsuite->setAttribute('errors', '0');
    }

    private function createFailedTestCases(\DOMDocument $dom, \DOMElement $testsuite, ReportSummary $reportSummary)
    {
        $assertionsCount = 0;
        foreach ($reportSummary->getChanged() as $file => $fixResult) {
            $testcase = $this->createFailedTestCase(
                $dom,
                $file,
                $fixResult,
                $reportSummary->shouldAddAppliedFixers()
            );
            $testsuite->appendChild($testcase);
            $assertionsCount += (int) $testcase->getAttribute('assertions');
        }

        $testsuite->setAttribute('tests', (string) \count($reportSummary->getChanged()));
        $testsuite->setAttribute('assertions', (string) $assertionsCount);
        $testsuite->setAttribute('failures', (string) $assertionsCount);
        $testsuite->setAttribute('errors', '0');
    }

    /**
     * @param string $file
     * @param bool   $shouldAddAppliedFixers
     *
     * @return \DOMElement
     */
    private function createFailedTestCase(\DOMDocument $dom, $file, array $fixResult, $shouldAddAppliedFixers)
    {
        $appliedFixersCount = \count($fixResult['appliedFixers']);

        $testName = str_replace('.', '_DOT_', Preg::replace('@\.'.pathinfo($file, PATHINFO_EXTENSION).'$@', '', $file));

        $testcase = $dom->createElement('testcase');
        $testcase->setAttribute('name', $testName);
        $testcase->setAttribute('file', $file);
        $testcase->setAttribute('assertions', (string) $appliedFixersCount);

        $failure = $dom->createElement('failure');
        $failure->setAttribute('type', 'code_style');
        $testcase->appendChild($failure);

        if ($shouldAddAppliedFixers) {
            $failureContent = "applied fixers:\n---------------\n";

            foreach ($fixResult['appliedFixers'] as $appliedFixer) {
                $failureContent .= "* {$appliedFixer}\n";
            }
        } else {
            $failureContent = "Wrong code style\n";
        }

        if (!empty($fixResult['diff'])) {
            $failureContent .= "\nDiff:\n---------------\n\n".$fixResult['diff'];
        }

        $failure->appendChild($dom->createCDATASection(trim($failureContent)));

        return $testcase;
    }
}
