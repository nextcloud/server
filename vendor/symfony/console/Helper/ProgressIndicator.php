<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Helper;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class ProgressIndicator
{
    private const FORMATS = [
        'normal' => ' %indicator% %message%',
        'normal_no_ansi' => ' %message%',

        'verbose' => ' %indicator% %message% (%elapsed:6s%)',
        'verbose_no_ansi' => ' %message% (%elapsed:6s%)',

        'very_verbose' => ' %indicator% %message% (%elapsed:6s%, %memory:6s%)',
        'very_verbose_no_ansi' => ' %message% (%elapsed:6s%, %memory:6s%)',
    ];

    private OutputInterface $output;
    private int $startTime;
    private ?string $format = null;
    private ?string $message = null;
    private array $indicatorValues;
    private int $indicatorCurrent;
    private int $indicatorChangeInterval;
    private float $indicatorUpdateTime;
    private bool $started = false;

    /**
     * @var array<string, callable>
     */
    private static array $formatters;

    /**
     * @param int        $indicatorChangeInterval Change interval in milliseconds
     * @param array|null $indicatorValues         Animated indicator characters
     */
    public function __construct(OutputInterface $output, ?string $format = null, int $indicatorChangeInterval = 100, ?array $indicatorValues = null)
    {
        $this->output = $output;

        $format ??= $this->determineBestFormat();
        $indicatorValues ??= ['-', '\\', '|', '/'];
        $indicatorValues = array_values($indicatorValues);

        if (2 > \count($indicatorValues)) {
            throw new InvalidArgumentException('Must have at least 2 indicator value characters.');
        }

        $this->format = self::getFormatDefinition($format);
        $this->indicatorChangeInterval = $indicatorChangeInterval;
        $this->indicatorValues = $indicatorValues;
        $this->startTime = time();
    }

    /**
     * Sets the current indicator message.
     *
     * @return void
     */
    public function setMessage(?string $message)
    {
        $this->message = $message;

        $this->display();
    }

    /**
     * Starts the indicator output.
     *
     * @return void
     */
    public function start(string $message)
    {
        if ($this->started) {
            throw new LogicException('Progress indicator already started.');
        }

        $this->message = $message;
        $this->started = true;
        $this->startTime = time();
        $this->indicatorUpdateTime = $this->getCurrentTimeInMilliseconds() + $this->indicatorChangeInterval;
        $this->indicatorCurrent = 0;

        $this->display();
    }

    /**
     * Advances the indicator.
     *
     * @return void
     */
    public function advance()
    {
        if (!$this->started) {
            throw new LogicException('Progress indicator has not yet been started.');
        }

        if (!$this->output->isDecorated()) {
            return;
        }

        $currentTime = $this->getCurrentTimeInMilliseconds();

        if ($currentTime < $this->indicatorUpdateTime) {
            return;
        }

        $this->indicatorUpdateTime = $currentTime + $this->indicatorChangeInterval;
        ++$this->indicatorCurrent;

        $this->display();
    }

    /**
     * Finish the indicator with message.
     *
     * @return void
     */
    public function finish(string $message)
    {
        if (!$this->started) {
            throw new LogicException('Progress indicator has not yet been started.');
        }

        $this->message = $message;
        $this->display();
        $this->output->writeln('');
        $this->started = false;
    }

    /**
     * Gets the format for a given name.
     */
    public static function getFormatDefinition(string $name): ?string
    {
        return self::FORMATS[$name] ?? null;
    }

    /**
     * Sets a placeholder formatter for a given name.
     *
     * This method also allow you to override an existing placeholder.
     *
     * @return void
     */
    public static function setPlaceholderFormatterDefinition(string $name, callable $callable)
    {
        self::$formatters ??= self::initPlaceholderFormatters();

        self::$formatters[$name] = $callable;
    }

    /**
     * Gets the placeholder formatter for a given name (including the delimiter char like %).
     */
    public static function getPlaceholderFormatterDefinition(string $name): ?callable
    {
        self::$formatters ??= self::initPlaceholderFormatters();

        return self::$formatters[$name] ?? null;
    }

    private function display(): void
    {
        if (OutputInterface::VERBOSITY_QUIET === $this->output->getVerbosity()) {
            return;
        }

        $this->overwrite(preg_replace_callback("{%([a-z\-_]+)(?:\:([^%]+))?%}i", function ($matches) {
            if ($formatter = self::getPlaceholderFormatterDefinition($matches[1])) {
                return $formatter($this);
            }

            return $matches[0];
        }, $this->format ?? ''));
    }

    private function determineBestFormat(): string
    {
        return match ($this->output->getVerbosity()) {
            // OutputInterface::VERBOSITY_QUIET: display is disabled anyway
            OutputInterface::VERBOSITY_VERBOSE => $this->output->isDecorated() ? 'verbose' : 'verbose_no_ansi',
            OutputInterface::VERBOSITY_VERY_VERBOSE,
            OutputInterface::VERBOSITY_DEBUG => $this->output->isDecorated() ? 'very_verbose' : 'very_verbose_no_ansi',
            default => $this->output->isDecorated() ? 'normal' : 'normal_no_ansi',
        };
    }

    /**
     * Overwrites a previous message to the output.
     */
    private function overwrite(string $message): void
    {
        if ($this->output->isDecorated()) {
            $this->output->write("\x0D\x1B[2K");
            $this->output->write($message);
        } else {
            $this->output->writeln($message);
        }
    }

    private function getCurrentTimeInMilliseconds(): float
    {
        return round(microtime(true) * 1000);
    }

    /**
     * @return array<string, \Closure>
     */
    private static function initPlaceholderFormatters(): array
    {
        return [
            'indicator' => fn (self $indicator) => $indicator->indicatorValues[$indicator->indicatorCurrent % \count($indicator->indicatorValues)],
            'message' => fn (self $indicator) => $indicator->message,
            'elapsed' => fn (self $indicator) => Helper::formatTime(time() - $indicator->startTime, 2),
            'memory' => fn () => Helper::formatMemory(memory_get_usage(true)),
        ];
    }
}
