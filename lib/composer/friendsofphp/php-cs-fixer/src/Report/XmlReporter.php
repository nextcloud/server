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

use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * @author Boris Gorbylev <ekho@ekho.name>
 *
 * @internal
 */
final class XmlReporter implements ReporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFormat()
    {
        return 'xml';
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
        // new nodes should be added to this or existing children
        $root = $dom->createElement('report');
        $dom->appendChild($root);

        $filesXML = $dom->createElement('files');
        $root->appendChild($filesXML);

        $i = 1;
        foreach ($reportSummary->getChanged() as $file => $fixResult) {
            $fileXML = $dom->createElement('file');
            $fileXML->setAttribute('id', (string) $i++);
            $fileXML->setAttribute('name', $file);
            $filesXML->appendChild($fileXML);

            if ($reportSummary->shouldAddAppliedFixers()) {
                $fileXML->appendChild($this->createAppliedFixersElement($dom, $fixResult));
            }

            if (!empty($fixResult['diff'])) {
                $fileXML->appendChild($this->createDiffElement($dom, $fixResult));
            }
        }

        if (0 !== $reportSummary->getTime()) {
            $root->appendChild($this->createTimeElement($reportSummary->getTime(), $dom));
        }

        if (0 !== $reportSummary->getMemory()) {
            $root->appendChild($this->createMemoryElement($reportSummary->getMemory(), $dom));
        }

        $dom->formatOutput = true;

        return $reportSummary->isDecoratedOutput() ? OutputFormatter::escape($dom->saveXML()) : $dom->saveXML();
    }

    /**
     * @param \DOMDocument $dom
     *
     * @return \DOMElement
     */
    private function createAppliedFixersElement($dom, array $fixResult)
    {
        $appliedFixersXML = $dom->createElement('applied_fixers');

        foreach ($fixResult['appliedFixers'] as $appliedFixer) {
            $appliedFixerXML = $dom->createElement('applied_fixer');
            $appliedFixerXML->setAttribute('name', $appliedFixer);
            $appliedFixersXML->appendChild($appliedFixerXML);
        }

        return $appliedFixersXML;
    }

    /**
     * @return \DOMElement
     */
    private function createDiffElement(\DOMDocument $dom, array $fixResult)
    {
        $diffXML = $dom->createElement('diff');
        $diffXML->appendChild($dom->createCDATASection($fixResult['diff']));

        return $diffXML;
    }

    /**
     * @param float $time
     *
     * @return \DOMElement
     */
    private function createTimeElement($time, \DOMDocument $dom)
    {
        $time = round($time / 1000, 3);

        $timeXML = $dom->createElement('time');
        $timeXML->setAttribute('unit', 's');
        $timeTotalXML = $dom->createElement('total');
        $timeTotalXML->setAttribute('value', (string) $time);
        $timeXML->appendChild($timeTotalXML);

        return $timeXML;
    }

    /**
     * @param float $memory
     *
     * @return \DOMElement
     */
    private function createMemoryElement($memory, \DOMDocument $dom)
    {
        $memory = round($memory / 1024 / 1024, 3);

        $memoryXML = $dom->createElement('memory');
        $memoryXML->setAttribute('value', (string) $memory);
        $memoryXML->setAttribute('unit', 'MB');

        return $memoryXML;
    }
}
