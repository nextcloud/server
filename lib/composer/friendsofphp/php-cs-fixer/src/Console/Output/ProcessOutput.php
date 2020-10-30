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

namespace PhpCsFixer\Console\Output;

use PhpCsFixer\FixerFileProcessedEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Output writer to show the process of a FixCommand.
 *
 * @internal
 */
final class ProcessOutput implements ProcessOutputInterface
{
    /**
     * File statuses map.
     *
     * @var array
     */
    private static $eventStatusMap = [
        FixerFileProcessedEvent::STATUS_UNKNOWN => ['symbol' => '?', 'format' => '%s', 'description' => 'unknown'],
        FixerFileProcessedEvent::STATUS_INVALID => ['symbol' => 'I', 'format' => '<bg=red>%s</bg=red>', 'description' => 'invalid file syntax (file ignored)'],
        FixerFileProcessedEvent::STATUS_SKIPPED => ['symbol' => 'S', 'format' => '<fg=cyan>%s</fg=cyan>', 'description' => 'skipped (cached or empty file)'],
        FixerFileProcessedEvent::STATUS_NO_CHANGES => ['symbol' => '.', 'format' => '%s', 'description' => 'no changes'],
        FixerFileProcessedEvent::STATUS_FIXED => ['symbol' => 'F', 'format' => '<fg=green>%s</fg=green>', 'description' => 'fixed'],
        FixerFileProcessedEvent::STATUS_EXCEPTION => ['symbol' => 'E', 'format' => '<bg=red>%s</bg=red>', 'description' => 'error'],
        FixerFileProcessedEvent::STATUS_LINT => ['symbol' => 'E', 'format' => '<bg=red>%s</bg=red>', 'description' => 'error'],
    ];

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var null|int
     */
    private $files;

    /**
     * @var int
     */
    private $processedFiles = 0;

    /**
     * @var null|int
     */
    private $symbolsPerLine;

    /**
     * @TODO 3.0 make all parameters mandatory (`null` not allowed)
     *
     * @param null|int $width
     * @param null|int $nbFiles
     */
    public function __construct(OutputInterface $output, EventDispatcherInterface $dispatcher, $width, $nbFiles)
    {
        $this->output = $output;
        $this->eventDispatcher = $dispatcher;
        $this->eventDispatcher->addListener(FixerFileProcessedEvent::NAME, [$this, 'onFixerFileProcessed']);
        $this->symbolsPerLine = $width;

        if (null !== $nbFiles) {
            $this->files = $nbFiles;

            //   max number of characters per line
            // - total length x 2 (e.g. "  1 / 123" => 6 digits and padding spaces)
            // - 11               (extra spaces, parentheses and percentage characters, e.g. " x / x (100%)")
            $this->symbolsPerLine = max(1, ($width ?: 80) - \strlen((string) $this->files) * 2 - 11);
        }
    }

    public function __destruct()
    {
        $this->eventDispatcher->removeListener(FixerFileProcessedEvent::NAME, [$this, 'onFixerFileProcessed']);
    }

    public function onFixerFileProcessed(FixerFileProcessedEvent $event)
    {
        if (
            null === $this->files
            && null !== $this->symbolsPerLine
            && 0 === $this->processedFiles % $this->symbolsPerLine
            && 0 !== $this->processedFiles
        ) {
            $this->output->writeln('');
        }

        $status = self::$eventStatusMap[$event->getStatus()];
        $this->output->write($this->output->isDecorated() ? sprintf($status['format'], $status['symbol']) : $status['symbol']);

        ++$this->processedFiles;

        if (null !== $this->files) {
            $symbolsOnCurrentLine = $this->processedFiles % $this->symbolsPerLine;
            $isLast = $this->processedFiles === $this->files;

            if (0 === $symbolsOnCurrentLine || $isLast) {
                $this->output->write(sprintf(
                    '%s %'.\strlen((string) $this->files).'d / %d (%3d%%)',
                    $isLast && 0 !== $symbolsOnCurrentLine ? str_repeat(' ', $this->symbolsPerLine - $symbolsOnCurrentLine) : '',
                    $this->processedFiles,
                    $this->files,
                    round($this->processedFiles / $this->files * 100)
                ));

                if (!$isLast) {
                    $this->output->writeln('');
                }
            }
        }
    }

    public function printLegend()
    {
        $symbols = [];

        foreach (self::$eventStatusMap as $status) {
            $symbol = $status['symbol'];
            if ('' === $symbol || isset($symbols[$symbol])) {
                continue;
            }

            $symbols[$symbol] = sprintf('%s-%s', $this->output->isDecorated() ? sprintf($status['format'], $symbol) : $symbol, $status['description']);
        }

        $this->output->write(sprintf("\nLegend: %s\n", implode(', ', $symbols)));
    }
}
