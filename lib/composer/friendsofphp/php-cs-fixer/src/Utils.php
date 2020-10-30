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

namespace PhpCsFixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tokenizer\Token;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author Graham Campbell <graham@alt-three.com>
 * @author Odín del Río <odin.drp@gmail.com>
 *
 * @internal
 */
final class Utils
{
    /**
     * Calculate a bitmask for given constant names.
     *
     * @param string[] $options constant names
     *
     * @return int
     */
    public static function calculateBitmask(array $options)
    {
        $bitmask = 0;

        foreach ($options as $optionName) {
            if (\defined($optionName)) {
                $bitmask |= \constant($optionName);
            }
        }

        return $bitmask;
    }

    /**
     * Converts a camel cased string to a snake cased string.
     *
     * @param string $string
     *
     * @return string
     */
    public static function camelCaseToUnderscore($string)
    {
        return strtolower(Preg::replace('/(?<!^)((?=[A-Z][^A-Z])|(?<![A-Z])(?=[A-Z]))/', '_', $string));
    }

    /**
     * Compare two integers for equality.
     *
     * We'll return 0 if they're equal, 1 if the first is bigger than the
     * second, and -1 if the second is bigger than the first.
     *
     * @param int $a
     * @param int $b
     *
     * @return int
     */
    public static function cmpInt($a, $b)
    {
        if ($a === $b) {
            return 0;
        }

        return $a < $b ? -1 : 1;
    }

    /**
     * Calculate the trailing whitespace.
     *
     * What we're doing here is grabbing everything after the final newline.
     *
     * @return string
     */
    public static function calculateTrailingWhitespaceIndent(Token $token)
    {
        if (!$token->isWhitespace()) {
            throw new \InvalidArgumentException(sprintf('The given token must be whitespace, got "%s".', $token->getName()));
        }

        $str = strrchr(
            str_replace(["\r\n", "\r"], "\n", $token->getContent()),
            "\n"
        );

        if (false === $str) {
            return '';
        }

        return ltrim($str, "\n");
    }

    /**
     * Perform stable sorting using provided comparison function.
     *
     * Stability is ensured by using Schwartzian transform.
     *
     * @param mixed[]  $elements
     * @param callable $getComparedValue a callable that takes a single element and returns the value to compare
     * @param callable $compareValues    a callable that compares two values
     *
     * @return mixed[]
     */
    public static function stableSort(array $elements, callable $getComparedValue, callable $compareValues)
    {
        array_walk($elements, static function (&$element, $index) use ($getComparedValue) {
            $element = [$element, $index, $getComparedValue($element)];
        });

        usort($elements, static function ($a, $b) use ($compareValues) {
            $comparison = $compareValues($a[2], $b[2]);

            if (0 !== $comparison) {
                return $comparison;
            }

            return self::cmpInt($a[1], $b[1]);
        });

        return array_map(static function (array $item) {
            return $item[0];
        }, $elements);
    }

    /**
     * Sort fixers by their priorities.
     *
     * @param FixerInterface[] $fixers
     *
     * @return FixerInterface[]
     */
    public static function sortFixers(array $fixers)
    {
        // Schwartzian transform is used to improve the efficiency and avoid
        // `usort(): Array was modified by the user comparison function` warning for mocked objects.
        return self::stableSort(
            $fixers,
            static function (FixerInterface $fixer) {
                return $fixer->getPriority();
            },
            static function ($a, $b) {
                return self::cmpInt($b, $a);
            }
        );
    }

    /**
     * Join names in natural language wrapped in backticks, e.g. `a`, `b` and `c`.
     *
     * @param string[] $names
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public static function naturalLanguageJoinWithBackticks(array $names)
    {
        if (empty($names)) {
            throw new \InvalidArgumentException('Array of names cannot be empty');
        }

        $names = array_map(static function ($name) {
            return sprintf('`%s`', $name);
        }, $names);

        $last = array_pop($names);

        if ($names) {
            return implode(', ', $names).' and '.$last;
        }

        return $last;
    }
}
