<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

/**
 * Exception thrown when attempting to create a number from a string with an invalid format.
 */
class NumberFormatException extends MathException
{
    public static function invalidFormat(string $value) : self
    {
        return new self(\sprintf(
            'The given value "%s" does not represent a valid number.',
            $value,
        ));
    }

    /**
     * @param string $char The failing character.
     *
     * @psalm-pure
     */
    public static function charNotInAlphabet(string $char) : self
    {
        $ord = \ord($char);

        if ($ord < 32 || $ord > 126) {
            $char = \strtoupper(\dechex($ord));

            if ($ord < 10) {
                $char = '0' . $char;
            }
        } else {
            $char = '"' . $char . '"';
        }

        return new self(\sprintf('Char %s is not a valid character in the given alphabet.', $char));
    }
}
