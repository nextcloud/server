<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use function count;
use function dirname;
use function file_put_contents;
use function is_string;
use function ksort;
use function max;
use function range;
use function str_contains;
use function time;
use DOMDocument;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\WriteOperationFailedException;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\Util\Filesystem;

final class Clover
{
    /**
     * @throws WriteOperationFailedException
     */
    public function process(CodeCoverage $coverage, ?string $target = null, ?string $name = null): string
    {
        $time = (string) time();

        $xmlDocument               = new DOMDocument('1.0', 'UTF-8');
        $xmlDocument->formatOutput = true;

        $xmlCoverage = $xmlDocument->createElement('coverage');
        $xmlCoverage->setAttribute('generated', $time);
        $xmlDocument->appendChild($xmlCoverage);

        $xmlProject = $xmlDocument->createElement('project');
        $xmlProject->setAttribute('timestamp', $time);

        if (is_string($name)) {
            $xmlProject->setAttribute('name', $name);
        }

        $xmlCoverage->appendChild($xmlProject);

        $packages = [];
        $report   = $coverage->getReport();

        foreach ($report as $item) {
            if (!$item instanceof File) {
                continue;
            }

            /* @var File $item */

            $xmlFile = $xmlDocument->createElement('file');
            $xmlFile->setAttribute('name', $item->pathAsString());

            $classes      = $item->classesAndTraits();
            $coverageData = $item->lineCoverageData();
            $lines        = [];
            $namespace    = 'global';

            foreach ($classes as $className => $class) {
                $classStatements        = 0;
                $coveredClassStatements = 0;
                $coveredMethods         = 0;
                $classMethods           = 0;

                foreach ($class['methods'] as $methodName => $method) {
                    if ($method['executableLines'] == 0) {
                        continue;
                    }

                    $classMethods++;
                    $classStatements        += $method['executableLines'];
                    $coveredClassStatements += $method['executedLines'];

                    if ($method['coverage'] == 100) {
                        $coveredMethods++;
                    }

                    $methodCount = 0;

                    foreach (range($method['startLine'], $method['endLine']) as $line) {
                        if (isset($coverageData[$line])) {
                            $methodCount = max($methodCount, count($coverageData[$line]));
                        }
                    }

                    $lines[$method['startLine']] = [
                        'ccn'        => $method['ccn'],
                        'count'      => $methodCount,
                        'crap'       => $method['crap'],
                        'type'       => 'method',
                        'visibility' => $method['visibility'],
                        'name'       => $methodName,
                    ];
                }

                if (!empty($class['package']['namespace'])) {
                    $namespace = $class['package']['namespace'];
                }

                $xmlClass = $xmlDocument->createElement('class');
                $xmlClass->setAttribute('name', $className);
                $xmlClass->setAttribute('namespace', $namespace);

                if (!empty($class['package']['fullPackage'])) {
                    $xmlClass->setAttribute(
                        'fullPackage',
                        $class['package']['fullPackage'],
                    );
                }

                if (!empty($class['package']['category'])) {
                    $xmlClass->setAttribute(
                        'category',
                        $class['package']['category'],
                    );
                }

                if (!empty($class['package']['package'])) {
                    $xmlClass->setAttribute(
                        'package',
                        $class['package']['package'],
                    );
                }

                if (!empty($class['package']['subpackage'])) {
                    $xmlClass->setAttribute(
                        'subpackage',
                        $class['package']['subpackage'],
                    );
                }

                $xmlFile->appendChild($xmlClass);

                $xmlMetrics = $xmlDocument->createElement('metrics');
                $xmlMetrics->setAttribute('complexity', (string) $class['ccn']);
                $xmlMetrics->setAttribute('methods', (string) $classMethods);
                $xmlMetrics->setAttribute('coveredmethods', (string) $coveredMethods);
                $xmlMetrics->setAttribute('conditionals', (string) $class['executableBranches']);
                $xmlMetrics->setAttribute('coveredconditionals', (string) $class['executedBranches']);
                $xmlMetrics->setAttribute('statements', (string) $classStatements);
                $xmlMetrics->setAttribute('coveredstatements', (string) $coveredClassStatements);
                $xmlMetrics->setAttribute('elements', (string) ($classMethods + $classStatements + $class['executableBranches']));
                $xmlMetrics->setAttribute('coveredelements', (string) ($coveredMethods + $coveredClassStatements + $class['executedBranches']));
                $xmlClass->appendChild($xmlMetrics);
            }

            foreach ($coverageData as $line => $data) {
                if ($data === null || isset($lines[$line])) {
                    continue;
                }

                $lines[$line] = [
                    'count' => count($data), 'type' => 'stmt',
                ];
            }

            ksort($lines);

            foreach ($lines as $line => $data) {
                $xmlLine = $xmlDocument->createElement('line');
                $xmlLine->setAttribute('num', (string) $line);
                $xmlLine->setAttribute('type', $data['type']);

                if (isset($data['name'])) {
                    $xmlLine->setAttribute('name', $data['name']);
                }

                if (isset($data['visibility'])) {
                    $xmlLine->setAttribute('visibility', $data['visibility']);
                }

                if (isset($data['ccn'])) {
                    $xmlLine->setAttribute('complexity', (string) $data['ccn']);
                }

                if (isset($data['crap'])) {
                    $xmlLine->setAttribute('crap', (string) $data['crap']);
                }

                $xmlLine->setAttribute('count', (string) $data['count']);
                $xmlFile->appendChild($xmlLine);
            }

            $linesOfCode = $item->linesOfCode();

            $xmlMetrics = $xmlDocument->createElement('metrics');
            $xmlMetrics->setAttribute('loc', (string) $linesOfCode['linesOfCode']);
            $xmlMetrics->setAttribute('ncloc', (string) $linesOfCode['nonCommentLinesOfCode']);
            $xmlMetrics->setAttribute('classes', (string) $item->numberOfClassesAndTraits());
            $xmlMetrics->setAttribute('methods', (string) $item->numberOfMethods());
            $xmlMetrics->setAttribute('coveredmethods', (string) $item->numberOfTestedMethods());
            $xmlMetrics->setAttribute('conditionals', (string) $item->numberOfExecutableBranches());
            $xmlMetrics->setAttribute('coveredconditionals', (string) $item->numberOfExecutedBranches());
            $xmlMetrics->setAttribute('statements', (string) $item->numberOfExecutableLines());
            $xmlMetrics->setAttribute('coveredstatements', (string) $item->numberOfExecutedLines());
            $xmlMetrics->setAttribute('elements', (string) ($item->numberOfMethods() + $item->numberOfExecutableLines() + $item->numberOfExecutableBranches()));
            $xmlMetrics->setAttribute('coveredelements', (string) ($item->numberOfTestedMethods() + $item->numberOfExecutedLines() + $item->numberOfExecutedBranches()));
            $xmlFile->appendChild($xmlMetrics);

            if ($namespace === 'global') {
                $xmlProject->appendChild($xmlFile);
            } else {
                if (!isset($packages[$namespace])) {
                    $packages[$namespace] = $xmlDocument->createElement(
                        'package',
                    );

                    $packages[$namespace]->setAttribute('name', $namespace);
                    $xmlProject->appendChild($packages[$namespace]);
                }

                $packages[$namespace]->appendChild($xmlFile);
            }
        }

        $linesOfCode = $report->linesOfCode();

        $xmlMetrics = $xmlDocument->createElement('metrics');
        $xmlMetrics->setAttribute('files', (string) count($report));
        $xmlMetrics->setAttribute('loc', (string) $linesOfCode['linesOfCode']);
        $xmlMetrics->setAttribute('ncloc', (string) $linesOfCode['nonCommentLinesOfCode']);
        $xmlMetrics->setAttribute('classes', (string) $report->numberOfClassesAndTraits());
        $xmlMetrics->setAttribute('methods', (string) $report->numberOfMethods());
        $xmlMetrics->setAttribute('coveredmethods', (string) $report->numberOfTestedMethods());
        $xmlMetrics->setAttribute('conditionals', (string) $report->numberOfExecutableBranches());
        $xmlMetrics->setAttribute('coveredconditionals', (string) $report->numberOfExecutedBranches());
        $xmlMetrics->setAttribute('statements', (string) $report->numberOfExecutableLines());
        $xmlMetrics->setAttribute('coveredstatements', (string) $report->numberOfExecutedLines());
        $xmlMetrics->setAttribute('elements', (string) ($report->numberOfMethods() + $report->numberOfExecutableLines() + $report->numberOfExecutableBranches()));
        $xmlMetrics->setAttribute('coveredelements', (string) ($report->numberOfTestedMethods() + $report->numberOfExecutedLines() + $report->numberOfExecutedBranches()));
        $xmlProject->appendChild($xmlMetrics);

        $buffer = $xmlDocument->saveXML();

        if ($target !== null) {
            if (!str_contains($target, '://')) {
                Filesystem::createDirectory(dirname($target));
            }

            if (@file_put_contents($target, $buffer) === false) {
                throw new WriteOperationFailedException($target);
            }
        }

        return $buffer;
    }
}
