<?php

namespace libphonenumber\Leniency;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;

class Possible extends AbstractLeniency
{
    protected static $level = 1;

    /**
     * Phone numbers accepted are PhoneNumberUtil::isPossibleNumber(), but not necessarily
     * PhoneNumberUtil::isValidNumber().
     *
     * @param PhoneNumber $number
     * @param string $candidate
     * @param PhoneNumberUtil $util
     * @return bool
     */
    public static function verify(PhoneNumber $number, $candidate, PhoneNumberUtil $util)
    {
        return $util->isPossibleNumber($number);
    }
}
