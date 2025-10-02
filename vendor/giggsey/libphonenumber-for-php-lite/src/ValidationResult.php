<?php

declare(strict_types=1);

namespace libphonenumber;

/**
 * Possible outcomes when testing if a PhoneNumber is possible.
 */
enum ValidationResult: int
{
    /**
     * The number length matches that of valid numbers for this region
     */
    case IS_POSSIBLE = 0;

    /**
     * The number has an invalid country calling code.
     */
    case INVALID_COUNTRY_CODE = 1;

    /**
     * The number is shorter than all valid numbers for this region.
     */
    case TOO_SHORT = 2;

    /**
     * The number is longer than all valid numbers for this region.
     */
    case TOO_LONG = 3;

    /**
     * The number length matches that of local numbers for this region only (i.e. numbers that may
     * be able to be dialled within an area, but do not have all the information to be dialled from
     * anywhere inside or outside the country).
     */
    case IS_POSSIBLE_LOCAL_ONLY = 4;

    /**
     * The number is longer than the shortest valid numbers for this region, shorter than the
     * longest valid numbers for this region, and does not itself have a number length that matches
     * valid numbers for this region. This can also be returned in the case where
     * isPossibleNumberForTypeWithReason was called, and there are no numbers of this type at all
     * for this region.
     */
    case INVALID_LENGTH = 5;
}
