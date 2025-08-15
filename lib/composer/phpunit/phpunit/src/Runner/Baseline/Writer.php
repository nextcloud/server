<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Runner\Baseline;

use function assert;
use function dirname;
use function file_put_contents;
use XMLWriter;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class Writer
{
    /**
     * @psalm-param non-empty-string $baselineFile
     */
    public function write(string $baselineFile, Baseline $baseline): void
    {
        $pathCalculator = new RelativePathCalculator(dirname($baselineFile));

        $writer = new XMLWriter;

        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument();

        $writer->startElement('files');
        $writer->writeAttribute('version', (string) Baseline::VERSION);

        foreach ($baseline->groupedByFileAndLine() as $file => $lines) {
            assert(!empty($file));

            $writer->startElement('file');
            $writer->writeAttribute('path', $pathCalculator->calculate($file));

            foreach ($lines as $line => $issues) {
                $writer->startElement('line');
                $writer->writeAttribute('number', (string) $line);
                $writer->writeAttribute('hash', $issues[0]->hash());

                foreach ($issues as $issue) {
                    $writer->startElement('issue');
                    $writer->writeCData($issue->description());
                    $writer->endElement();
                }

                $writer->endElement();
            }

            $writer->endElement();
        }

        $writer->endElement();

        file_put_contents($baselineFile, $writer->outputMemory());
    }
}
