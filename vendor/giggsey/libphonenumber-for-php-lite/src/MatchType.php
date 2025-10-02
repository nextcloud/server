<?php

declare(strict_types=1);

namespace libphonenumber;

/**
 * Types of phone number matches
 * See detailed description beside the isNumberMatch() method
 */
enum MatchType: int
{
    case NOT_A_NUMBER = 0;
    case NO_MATCH = 1;
    case SHORT_NSN_MATCH = 2;
    case NSN_MATCH = 3;
    case EXACT_MATCH = 4;
}
