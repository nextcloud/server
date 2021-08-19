<?php

namespace libphonenumber;

/**
 * Possible outcomes when testing if a PhoneNumber is possible.
 */
class ValidationResult
{
    /**
     * The number length matches that of valid numbers for this region
     */
    const IS_POSSIBLE = 0;

    /**
     * The number has an invalid country calling code.
     */
    const INVALID_COUNTRY_CODE = 1;

    /**
     * The number is shorter than all valid numbers for this region.
     */
    const TOO_SHORT = 2;

    /**
     * The number is longer than all valid numbers for this region.
     */
    const TOO_LONG = 3;

    /**
     * The number length matches that of local numbers for this region only (i.e. numbers that may
     * be able to be dialled within an area, but do not have all the information to be dialled from
     * anywhere inside or outside the country).
     */
    const IS_POSSIBLE_LOCAL_ONLY = 4;

    /**
     * The number is longer than the shortest valid numbers for this region, shorter than the
     * longest valid numbers for this region, and does not itself have a number length that matches
     * valid numbers for this region. This can also be returned in the case where
     * isPossibleNumberForTypeWithReason was called, and there are no numbers of this type at all
     * for this region.
     */
    const INVALID_LENGTH = 5;
}
