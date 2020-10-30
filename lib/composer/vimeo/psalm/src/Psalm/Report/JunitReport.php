<?php
namespace Psalm\Report;

use DOMDocument;
use DOMElement;
use Psalm\Config;
use Psalm\Report;
use Psalm\Internal\Analyzer\IssueData;
use const ENT_XML1;
use const ENT_QUOTES;
use function count;
use function htmlspecialchars;
use function trim;

/**
 * based on https://github.com/m50/psalm-json-to-junit
 * Copyright (c) Marisa Clardy marisa@clardy.eu
 *
 * with a few modifications
 */
class JunitReport extends Report
{
    public function create(): string
    {
        $errors = 0;
        $tests = 0;

        $ndata = [];

        foreach ($this->issues_data as $error) {
            $is_error = $error->severity === Config::REPORT_ERROR;
            $is_warning = $error->severity === Config::REPORT_INFO;

            if (!$is_error && !$is_warning) {
                continue;
            }

            if ($is_error) {
                $errors++;
            }

            $tests++;

            $fname = $error->file_name;

            if (!isset($ndata[$fname])) {
                $ndata[$fname] = [
                    'errors'   => $is_error ? 1 : 0,
                    'warnings' => $is_warning ? 1 : 0,
                    'failures' => [],
                ];
            } else {
                if ($is_error) {
                    $ndata[$fname]['errors']++;
                } else {
                    $ndata[$fname]['warnings']++;
                }
            }

            $ndata[$fname]['failures'][] = $error;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $schema = 'https://raw.githubusercontent.com/junit-team/'.
            'junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd';

        $suites = $dom->createElement('testsuites');

        $suites->setAttribute('failures', (string) $errors);
        $suites->setAttribute('errors', '0');
        $suites->setAttribute('name', 'psalm');
        $suites->setAttribute('tests', (string) $tests);
        $suites->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $suites->setAttribute('xsi:noNamespaceSchemaLocation', $schema);
        $dom->appendChild($suites);

        if (!count($ndata)) {
            $suites->setAttribute('tests', '1');

            $testsuite = $dom->createElement('testsuite');
            $testsuite->setAttribute('name', 'psalm');
            $testsuite->setAttribute('failures', '0');
            $testsuite->setAttribute('errors', '0');
            $testsuite->setAttribute('tests', '1');

            $testcase = $dom->createElement('testcase');
            $testcase->setAttribute('name', 'psalm');
            $testsuite->appendChild($testcase);

            $suites->appendChild($testsuite);
        } else {
            foreach ($ndata as $file => $report) {
                $this->createTestSuite($dom, $suites, $file, $report);
            }
        }



        return $dom->saveXML();
    }

    /**
     * @param  array{
     *         errors: int,
     *         warnings: int,
     *         failures: list<IssueData>
     *         } $report
     */
    private function createTestSuite(DOMDocument $dom, DOMElement $parent, string $file, array $report): void
    {
        $totalTests = $report['errors'] + $report['warnings'];
        if ($totalTests < 1) {
            $totalTests = 1;
        }

        $testsuite = $dom->createElement('testsuite');
        $testsuite->setAttribute('name', $file);
        $testsuite->setAttribute('failures', (string) $report['errors']);
        $testsuite->setAttribute('errors', '0');
        $testsuite->setAttribute('tests', (string) $totalTests);

        $failuresByType = $this->groupByType($report['failures']);

        foreach ($failuresByType as $type => $data) {
            foreach ($data as $d) {
                $testcase = $dom->createElement('testcase');
                $testcase->setAttribute('name', "{$file}:{$d->line_from}");
                $testcase->setAttribute('classname', $type);
                $testcase->setAttribute('assertions', (string) count($data));

                if ($d->severity === Config::REPORT_ERROR) {
                    $issue = $dom->createElement('failure');
                    $issue->setAttribute('type', $type);
                } else {
                    $issue = $dom->createElement('skipped');
                }
                $issue->nodeValue = $this->dataToOutput($d);

                $testcase->appendChild($issue);
                $testsuite->appendChild($testcase);
            }
        }
        $parent->appendChild($testsuite);
    }

    /**
     * @param  list<IssueData> $failures
     *
     * @return array<string, non-empty-list<IssueData>>
     */
    private function groupByType(array $failures): array
    {
        $nfailures = [];

        foreach ($failures as $failure) {
            $nfailures[$failure->type][] = $failure;
        }

        return $nfailures;
    }

    private function dataToOutput(IssueData $data): string
    {
        $ret = 'message: ' . htmlspecialchars(trim($data->message), ENT_XML1 | ENT_QUOTES) . "\n";
        $ret .= 'type: ' . trim($data->type) . "\n";
        $ret .= 'snippet: ' . htmlspecialchars(trim($data->snippet), ENT_XML1 | ENT_QUOTES) . "\n";
        $ret .= 'selected_text: ' . htmlspecialchars(trim($data->selected_text)) . "\n";
        $ret .= 'line: ' . $data->line_from . "\n";
        $ret .= 'column_from: ' . $data->column_from . "\n";
        $ret .= 'column_to: ' . $data->column_to . "\n";

        return $ret;
    }
}
