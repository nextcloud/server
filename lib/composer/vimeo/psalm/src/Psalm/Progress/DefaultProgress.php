<?php
namespace Psalm\Progress;

use function str_repeat;
use function strlen;

class DefaultProgress extends LongProgress
{
    private const TOO_MANY_FILES = 1500;

    // Update the progress bar at most once per 0.1 seconds.
    // This reduces flickering and reduces the amount of time spent writing to STDERR and updating the terminal.
    private const PROGRESS_BAR_SAMPLE_INTERVAL = 0.1;

    /** @var float the last time when the progress bar UI was updated */
    private $previous_update_time = 0.0;

    public function taskDone(int $level): void
    {
        if ($this->number_of_tasks > self::TOO_MANY_FILES) {
            ++$this->progress;

            // Source for rate limiting:
            // https://github.com/phan/phan/blob/9a788581ee1a4e1c35bebf89c435fd8a238c1d17/src/Phan/CLI.php
            $time = \microtime(true);

            // If not enough time has elapsed, then don't update the progress bar.
            // Making the update frequency based on time (instead of the number of files)
            // prevents the terminal from rapidly flickering while processing small/empty files,
            // and reduces the time spent writing to stderr.
            if ($time - $this->previous_update_time < self::PROGRESS_BAR_SAMPLE_INTERVAL) {
                // Make sure to output the section for 100% completion regardless of limits, to avoid confusion.
                if ($this->progress !== $this->number_of_tasks) {
                    return;
                }
            }
            $this->previous_update_time = $time;

            $inner_progress = self::renderInnerProgressBar(
                self::NUMBER_OF_COLUMNS,
                $this->progress / $this->number_of_tasks
            );

            $this->write($inner_progress . ' ' . $this->getOverview() . "\r");
        } else {
            parent::taskDone($level);
        }
    }

    /**
     * Fully stolen from
     * https://github.com/phan/phan/blob/d61a624b1384ea220f39927d53fd656a65a75fac/src/Phan/CLI.php
     * Renders a unicode progress bar that goes from light (left) to dark (right)
     * The length in the console is the positive integer $length
     *
     * @see https://en.wikipedia.org/wiki/Block_Elements
     */
    private static function renderInnerProgressBar(int $length, float $p) : string
    {
        $current_float = $p * $length;
        $current = (int)$current_float;
        $rest = \max($length - $current, 0);

        if (!self::doesTerminalSupportUtf8()) {
            // Show a progress bar of "XXXX>------" in Windows when utf-8 is unsupported.
            $progress_bar = str_repeat('X', $current);
            $delta = $current_float - $current;
            if ($delta > 0.5) {
                $progress_bar .= '>' . str_repeat('-', $rest - 1);
            } else {
                $progress_bar .= str_repeat('-', $rest);
            }

            return $progress_bar;
        }

        // The left-most characters are "Light shade"
        $progress_bar = str_repeat("\u{2588}", $current);
        $delta = $current_float - $current;
        if ($delta > 3.0 / 4) {
            $progress_bar .= "\u{258A}" . str_repeat("\u{2591}", $rest - 1);
        } elseif ($delta > 2.0 / 4) {
            $progress_bar .= "\u{258C}" . str_repeat("\u{2591}", $rest - 1);
        } elseif ($delta > 1.0 / 4) {
            $progress_bar .= "\u{258E}" . str_repeat("\u{2591}", $rest - 1);
        } else {
            $progress_bar .= str_repeat("\u{2591}", $rest);
        }

        return $progress_bar;
    }

    public function finish(): void
    {
        if ($this->number_of_tasks > self::TOO_MANY_FILES) {
            $this->write(str_repeat(' ', self::NUMBER_OF_COLUMNS + strlen($this->getOverview()) + 1) . "\r");
        } else {
            parent::finish();
        }
    }
}
