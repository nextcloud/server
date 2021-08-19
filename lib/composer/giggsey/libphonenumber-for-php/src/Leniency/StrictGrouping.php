<?php

namespace libphonenumber\Leniency;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberMatcher;
use libphonenumber\PhoneNumberUtil;

class StrictGrouping extends AbstractLeniency
{
    protected static $level = 3;

    /**
     * Phone numbers accepted are PhoneNumberUtil::isValidNumber() and are grouped
     * in a possible way for this locale. For example, a US number written as
     * "65 02 53 00 00" and "650253 0000" are not accepted at this leniency level, whereas
     * "650 253 0000", "650 2530000" or "6502530000" are.
     * Numbers with more than one '/' symbol in the national significant number are also dropped at
     * this level.
     *
     * Warning: This level might result in lower coverage especially for regions outside of country
     * code "+1". If you are not sure about which level to use, email the discussion group
     * libphonenumber-discuss@googlegroups.com.
     *
     * @param PhoneNumber $number
     * @param string $candidate
     * @param PhoneNumberUtil $util
     * @return bool
     */
    public static function verify(PhoneNumber $number, $candidate, PhoneNumberUtil $util)
    {
        if (!$util->isValidNumber($number)
            || !PhoneNumberMatcher::containsOnlyValidXChars($number, $candidate, $util)
            || PhoneNumberMatcher::containsMoreThanOneSlashInNationalNumber($number, $candidate)
            || !PhoneNumberMatcher::isNationalPrefixPresentIfRequired($number, $util)
        ) {
            return false;
        }

        return PhoneNumberMatcher::checkNumberGroupingIsValid(
            $number,
            $candidate,
            $util,
            function (PhoneNumberUtil $util, PhoneNumber $number, $normalizedCandidate, $expectedNumberGroups) {
                return PhoneNumberMatcher::allNumberGroupsRemainGrouped(
                    $util,
                    $number,
                    $normalizedCandidate,
                    $expectedNumberGroups
                );
            }
        );
    }
}
