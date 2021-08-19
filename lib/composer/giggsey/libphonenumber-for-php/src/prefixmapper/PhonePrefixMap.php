<?php

namespace libphonenumber\prefixmapper;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;

/**
 * A utility that maps phone number prefixes to a description string,
 * which may be, for example, the geographical area the prefix covers.
 *
 * Class PhonePrefixMap
 * @package libphonenumber\prefixmapper
 */
class PhonePrefixMap
{
    protected $phonePrefixMapStorage = array();
    /**
     * @var PhoneNumberUtil
     */
    protected $phoneUtil;

    public function __construct($map)
    {
        $this->phonePrefixMapStorage = $map;
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    /**
     * Returns the description of the {@code $number}. This method distinguishes the case of an invalid
     * prefix and a prefix for which the name is not available in the current language. If the
     * description is not available in the current language an empty string is returned. If no
     * description was found for the provided number, null is returned.
     *
     * @param PhoneNumber $number The phone number to look up
     * @return string|null the description of the number
     */
    public function lookup(PhoneNumber $number)
    {
        $phonePrefix = $number->getCountryCode() . $this->phoneUtil->getNationalSignificantNumber($number);

        return $this->lookupKey($phonePrefix);
    }

    public function lookupKey($key)
    {
        if (count($this->phonePrefixMapStorage) == 0) {
            return null;
        }

        while (strlen($key) > 0) {
            if (array_key_exists($key, $this->phonePrefixMapStorage)) {
                return $this->phonePrefixMapStorage[$key];
            }

            $key = substr($key, 0, -1);
        }

        return null;
    }
}
