<?php

namespace libphonenumber;

/**
 * Types of phone number matches
 * See detailed description beside the isNumberMatch() method
 */
class MatchType
{
    const NOT_A_NUMBER = 0;
    const NO_MATCH = 1;
    const SHORT_NSN_MATCH = 2;
    const NSN_MATCH = 3;
    const EXACT_MATCH = 4;
}
