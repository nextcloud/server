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

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\String\UnicodeString;

/**
 * Helper is the base class for all helper classes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Helper implements HelperInterface
{
    protected $helperSet;

    /**
     * @return void
     */
    public function setHelperSet(?HelperSet $helperSet = null)
    {
        if (1 > \func_num_args()) {
            trigger_deprecation('symfony/console', '6.2', 'Calling "%s()" without any arguments is deprecated, pass null explicitly instead.', __METHOD__);
        }
        $this->helperSet = $helperSet;
    }

    public function getHelperSet(): ?HelperSet
    {
        return $this->helperSet;
    }

    /**
     * Returns the width of a string, using mb_strwidth if it is available.
     * The width is how many characters positions the string will use.
     */
    public static function width(?string $string): int
    {
        $string ??= '';

        if (preg_match('//u', $string)) {
            return (new UnicodeString($string))->width(false);
        }

        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return \strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }

    /**
     * Returns the length of a string, using mb_strlen if it is available.
     * The length is related to how many bytes the string will use.
     */
    public static function length(?string $string): int
    {
        $string ??= '';

        if (preg_match('//u', $string)) {
            return (new UnicodeString($string))->length();
        }

        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return \strlen($string);
        }

        return mb_strlen($string, $encoding);
    }

    /**
     * Returns the subset of a string, using mb_substr if it is available.
     */
    public static function substr(?string $string, int $from, ?int $length = null): string
    {
        $string ??= '';

        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return substr($string, $from, $length);
        }

        return mb_substr($string, $from, $length, $encoding);
    }

    /**
     * @return string
     */
    public static function formatTime(int|float $secs, int $precision = 1)
    {
        $secs = (int) floor($secs);

        if (0 === $secs) {
            return '< 1 sec';
        }

        static $timeFormats = [
            [1, '1 sec', 'secs'],
            [60, '1 min', 'mins'],
            [3600, '1 hr', 'hrs'],
            [86400, '1 day', 'days'],
        ];

        $times = [];
        foreach ($timeFormats as $index => $format) {
            $seconds = isset($timeFormats[$index + 1]) ? $secs % $timeFormats[$index + 1][0] : $secs;

            if (isset($times[$index - $precision])) {
                unset($times[$index - $precision]);
            }

            if (0 === $seconds) {
                continue;
            }

            $unitCount = ($seconds / $format[0]);
            $times[$index] = 1 === $unitCount ? $format[1] : $unitCount.' '.$format[2];

            if ($secs === $seconds) {
                break;
            }

            $secs -= $seconds;
        }

        return implode(', ', array_reverse($times));
    }

    /**
     * @return string
     */
    public static function formatMemory(int $memory)
    {
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GiB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MiB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KiB', $memory / 1024);
        }

        return sprintf('%d B', $memory);
    }

    /**
     * @return string
     */
    public static function removeDecoration(OutputFormatterInterface $formatter, ?string $string)
    {
        $isDecorated = $formatter->isDecorated();
        $formatter->setDecorated(false);
        // remove <...> formatting
        $string = $formatter->format($string ?? '');
        // remove already formatted characters
        $string = preg_replace("/\033\[[^m]*m/", '', $string ?? '');
        // remove terminal hyperlinks
        $string = preg_replace('/\\033]8;[^;]*;[^\\033]*\\033\\\\/', '', $string ?? '');
        $formatter->setDecorated($isDecorated);

        return $string;
    }
}
