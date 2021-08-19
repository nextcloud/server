<?php

namespace libphonenumber\prefixmapper;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;

class PrefixTimeZonesMap
{
    /*

    protected final PhonePrefixMap phonePrefixMap = new PhonePrefixMap();
    protected static final String RAW_STRING_TIMEZONES_SEPARATOR = "&";
    */
    const RAW_STRING_TIMEZONES_SEPARATOR = '&';
    protected $phonePrefixMap;

    public function __construct($map)
    {
        $this->phonePrefixMap = new PhonePrefixMap($map);
    }

    /**
     * As per {@link #lookupTimeZonesForNumber(long)}, but receives the number as a PhoneNumber
     * instead of a long.
     *
     * @param $number PhoneNumber the phone number to look up
     * @return array the list of corresponding time zones
     */
    public function lookupTimeZonesForNumber(PhoneNumber $number)
    {
        $phonePrefix = $number->getCountryCode() . PhoneNumberUtil::getInstance()->getNationalSignificantNumber(
            $number
        );

        return $this->lookupTimeZonesForNumberKey($phonePrefix);
    }

    /**
     * Returns the list of time zones {@code key} corresponds to.
     *
     * <p>{@code key} could be the calling country code and the full significant number of a
     * certain number, or it could be just a phone-number prefix.
     * For example, the full number 16502530000 (from the phone-number +1 650 253 0000) is a valid
     * input. Also, any of its prefixes, such as 16502, is also valid.
     *
     * @param $key int the key to look up
     * @return array the list of corresponding time zones
     */
    protected function lookupTimeZonesForNumberKey($key)
    {
        // Lookup in the map data. The returned String may consist of several time zones, so it must be
        // split.
        $timezonesString = $this->phonePrefixMap->lookupKey($key);

        if ($timezonesString === null) {
            return array();
        }

        return $this->tokenizeRawOutputString($timezonesString);
    }

    /**
     * Split {@code timezonesString} into all the time zones that are part of it.
     *
     * @param $timezonesString String
     * @return array
     */
    protected function tokenizeRawOutputString($timezonesString)
    {
        return explode(static::RAW_STRING_TIMEZONES_SEPARATOR, $timezonesString);
    }

    /**
     * Returns the list of time zones {@code number}'s calling country code corresponds to.
     *
     * @param $number PhoneNumber the phone number to look up
     * @return array the list of corresponding time zones
     */
    public function lookupCountryLevelTimeZonesForNumber(PhoneNumber $number)
    {
        return $this->lookupTimeZonesForNumberKey($number->getCountryCode());
    }
}
