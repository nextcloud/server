<?php
namespace Psalm\Progress;

use const PHP_EOL;
use function floor;
use function sprintf;
use function str_repeat;
use function strlen;

class LongProgress extends Progress
{
    public const NUMBER_OF_COLUMNS = 60;

    /** @var int|null */
    protected $number_of_tasks;

    /** @var int */
    protected $progress = 0;

    /** @var bool */
    protected $print_errors = false;

    /** @var bool */
    protected $print_infos = false;

    public function __construct(bool $print_errors = true, bool $print_infos = true)
    {
        $this->print_errors = $print_errors;
        $this->print_infos = $print_infos;
    }

    public function startScanningFiles(): void
    {
        $this->write('Scanning files...' . "\n");
    }

    public function startAnalyzingFiles(): void
    {
        $this->write('Analyzing files...' . "\n\n");
    }

    public function startAlteringFiles(): void
    {
        $this->write('Altering files...' . "\n");
    }

    public function alterFileDone(string $file_name) : void
    {
        $this->write('Altered ' . $file_name . "\n");
    }

    public function start(int $number_of_tasks): void
    {
        $this->number_of_tasks = $number_of_tasks;
        $this->progress = 0;
    }

    public function taskDone(int $level): void
    {
        if ($level === 0 || ($level === 1 && !$this->print_infos) || !$this->print_errors) {
            $this->write(self::doesTerminalSupportUtf8() ? '░' : '_');
        } elseif ($level === 1) {
            $this->write('I');
        } else {
            $this->write('E');
        }

        ++$this->progress;

        if (($this->progress % self::NUMBER_OF_COLUMNS) !== 0) {
            return;
        }

        $this->printOverview();
        $this->write(PHP_EOL);
    }

    public function finish(): void
    {
        $this->write(PHP_EOL);
    }

    protected function getOverview() : string
    {
        if ($this->number_of_tasks === null) {
            throw new \LogicException('Progress::start() should be called before Progress::startDone()');
        }

        $leadingSpaces = 1 + strlen((string) $this->number_of_tasks) - strlen((string) $this->progress);
        // Don't show 100% unless this is the last line of the progress bar.
        $percentage = floor($this->progress / $this->number_of_tasks * 100);

        return sprintf(
            '%s%s / %s (%s%%)',
            str_repeat(' ', $leadingSpaces),
            $this->progress,
            $this->number_of_tasks,
            $percentage
        );
    }

    private function printOverview(): void
    {
        $this->write($this->getOverview());
    }
}
