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

use PhpCsFixer\Differ\DiffConsoleFormatter;

/**
 * @author Boris Gorbylev <ekho@ekho.name>
 *
 * @internal
 */
final class TextReporter implements ReporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFormat()
    {
        return 'txt';
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ReportSummary $reportSummary)
    {
        $output = '';

        $i = 0;
        foreach ($reportSummary->getChanged() as $file => $fixResult) {
            ++$i;
            $output .= sprintf('%4d) %s', $i, $file);

            if ($reportSummary->shouldAddAppliedFixers()) {
                $output .= $this->getAppliedFixers($reportSummary->isDecoratedOutput(), $fixResult);
            }

            $output .= $this->getDiff($reportSummary->isDecoratedOutput(), $fixResult);
            $output .= PHP_EOL;
        }

        return $output.$this->getFooter($reportSummary->getTime(), $reportSummary->getMemory(), $reportSummary->isDryRun());
    }

    /**
     * @param bool $isDecoratedOutput
     *
     * @return string
     */
    private function getAppliedFixers($isDecoratedOutput, array $fixResult)
    {
        return sprintf(
            $isDecoratedOutput ? ' (<comment>%s</comment>)' : ' (%s)',
            implode(', ', $fixResult['appliedFixers'])
        );
    }

    /**
     * @param bool $isDecoratedOutput
     *
     * @return string
     */
    private function getDiff($isDecoratedOutput, array $fixResult)
    {
        if (empty($fixResult['diff'])) {
            return '';
        }

        $diffFormatter = new DiffConsoleFormatter($isDecoratedOutput, sprintf(
            '<comment>      ---------- begin diff ----------</comment>%s%%s%s<comment>      ----------- end diff -----------</comment>',
            PHP_EOL,
            PHP_EOL
        ));

        return PHP_EOL.$diffFormatter->format($fixResult['diff']).PHP_EOL;
    }

    /**
     * @param int  $time
     * @param int  $memory
     * @param bool $isDryRun
     *
     * @return string
     */
    private function getFooter($time, $memory, $isDryRun)
    {
        if (0 === $time || 0 === $memory) {
            return '';
        }

        return PHP_EOL.sprintf(
            '%s all files in %.3f seconds, %.3f MB memory used'.PHP_EOL,
            $isDryRun ? 'Checked' : 'Fixed',
            $time / 1000,
            $memory / 1024 / 1024
        );
    }
}
