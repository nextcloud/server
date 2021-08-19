<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

/**
 * Exception thrown when a number cannot be represented at the requested scale without rounding.
 */
class RoundingNecessaryException extends MathException
{
    /**
     * @return RoundingNecessaryException
     *
     * @psalm-pure
     */
    public static function roundingNecessary() : RoundingNecessaryException
    {
        return new self('Rounding is necessary to represent the result of the operation at this scale.');
    }
}
