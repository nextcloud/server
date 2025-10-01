<?php

declare(strict_types=1);

namespace Brick\Math;

/**
 * Specifies a rounding behavior for numerical operations capable of discarding precision.
 *
 * Each rounding mode indicates how the least significant returned digit of a rounded result
 * is to be calculated. If fewer digits are returned than the digits needed to represent the
 * exact numerical result, the discarded digits will be referred to as the discarded fraction
 * regardless the digits' contribution to the value of the number. In other words, considered
 * as a numerical value, the discarded fraction could have an absolute value greater than one.
 */
enum RoundingMode
{
    /**
     * Asserts that the requested operation has an exact result, hence no rounding is necessary.
     *
     * If this rounding mode is specified on an operation that yields a result that
     * cannot be represented at the requested scale, a RoundingNecessaryException is thrown.
     */
    case UNNECESSARY;

    /**
     * Rounds away from zero.
     *
     * Always increments the digit prior to a nonzero discarded fraction.
     * Note that this rounding mode never decreases the magnitude of the calculated value.
     */
    case UP;

    /**
     * Rounds towards zero.
     *
     * Never increments the digit prior to a discarded fraction (i.e., truncates).
     * Note that this rounding mode never increases the magnitude of the calculated value.
     */
    case DOWN;

    /**
     * Rounds towards positive infinity.
     *
     * If the result is positive, behaves as for UP; if negative, behaves as for DOWN.
     * Note that this rounding mode never decreases the calculated value.
     */
    case CEILING;

    /**
     * Rounds towards negative infinity.
     *
     * If the result is positive, behave as for DOWN; if negative, behave as for UP.
     * Note that this rounding mode never increases the calculated value.
     */
    case FLOOR;

    /**
     * Rounds towards "nearest neighbor" unless both neighbors are equidistant, in which case round up.
     *
     * Behaves as for UP if the discarded fraction is >= 0.5; otherwise, behaves as for DOWN.
     * Note that this is the rounding mode commonly taught at school.
     */
    case HALF_UP;

    /**
     * Rounds towards "nearest neighbor" unless both neighbors are equidistant, in which case round down.
     *
     * Behaves as for UP if the discarded fraction is > 0.5; otherwise, behaves as for DOWN.
     */
    case HALF_DOWN;

    /**
     * Rounds towards "nearest neighbor" unless both neighbors are equidistant, in which case round towards positive infinity.
     *
     * If the result is positive, behaves as for HALF_UP; if negative, behaves as for HALF_DOWN.
     */
    case HALF_CEILING;

    /**
     * Rounds towards "nearest neighbor" unless both neighbors are equidistant, in which case round towards negative infinity.
     *
     * If the result is positive, behaves as for HALF_DOWN; if negative, behaves as for HALF_UP.
     */
    case HALF_FLOOR;

    /**
     * Rounds towards the "nearest neighbor" unless both neighbors are equidistant, in which case rounds towards the even neighbor.
     *
     * Behaves as for HALF_UP if the digit to the left of the discarded fraction is odd;
     * behaves as for HALF_DOWN if it's even.
     *
     * Note that this is the rounding mode that statistically minimizes
     * cumulative error when applied repeatedly over a sequence of calculations.
     * It is sometimes known as "Banker's rounding", and is chiefly used in the USA.
     */
    case HALF_EVEN;
}
