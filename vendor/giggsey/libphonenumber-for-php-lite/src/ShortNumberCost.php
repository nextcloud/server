<?php

declare(strict_types=1);

namespace libphonenumber;

/**
 * Cost categories of short numbers
 * @package libphonenumber
 */
enum ShortNumberCost: int
{
    case TOLL_FREE = 3;
    case PREMIUM_RATE = 4;
    case STANDARD_RATE = 30;
    case UNKNOWN_COST = 10;
}
