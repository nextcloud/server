<?php
/**
 * Created by PhpStorm.
 * User: giggsey
 * Date: 14/10/13
 * Time: 16:00
 */

namespace libphonenumber;

use libphonenumber\prefixmapper\PrefixTimeZonesMap;

class PhoneNumberToTimeZonesMapper
{
    const UNKNOWN_TIMEZONE = 'Etc/Unknown';
    const MAPPING_DATA_DIRECTORY = '/timezone/data/';
    const MAPPING_DATA_FILE_NAME = 'map_data.php';
    /**
     * @var PhoneNumberToTimeZonesMapper
     */
    protected static $instance;
    protected $unknownTimeZoneList = array();
    /**
     * @var PhoneNumberUtil
     */
    protected $phoneUtil;
    protected $prefixTimeZonesMap;

    protected function __construct($phonePrefixDataDirectory)
    {
        $this->prefixTimeZonesMap = static::loadPrefixTimeZonesMapFromFile(
            \dirname(__FILE__) . $phonePrefixDataDirectory . DIRECTORY_SEPARATOR . static::MAPPING_DATA_FILE_NAME
        );
        $this->phoneUtil = PhoneNumberUtil::getInstance();

        $this->unknownTimeZoneList[] = static::UNKNOWN_TIMEZONE;
    }

    protected static function loadPrefixTimeZonesMapFromFile($path)
    {
        if (!\is_readable($path)) {
            throw new \InvalidArgumentException('Mapping file can not be found');
        }

        $data = require $path;

        $map = new PrefixTimeZonesMap($data);

        return $map;
    }

    /**
     * Gets a {@link PhoneNumberToTimeZonesMapper} instance.
     *
     * <p> The {@link PhoneNumberToTimeZonesMapper} is implemented as a singleton. Therefore, calling
     * this method multiple times will only result in one instance being created.
     *
     * @param $mappingDir
     * @return PhoneNumberToTimeZonesMapper instance
     */
    public static function getInstance($mappingDir = self::MAPPING_DATA_DIRECTORY)
    {
        if (static::$instance === null) {
            static::$instance = new static($mappingDir);
        }

        return static::$instance;
    }

    /**
     * Returns a String with the ICU unknown time zone.
     * @return string
     */
    public static function getUnknownTimeZone()
    {
        return static::UNKNOWN_TIMEZONE;
    }

    /**
     * As per {@link #getTimeZonesForGeographicalNumber(PhoneNumber)} but explicitly checks
     * the validity of the number passed in.
     *
     * @param $number PhoneNumber the phone number for which we want to get the time zones to which it belongs
     * @return array a list of the corresponding time zones or a single element list with the default
     *     unknown time zone if no other time zone was found or if the number was invalid
     */
    public function getTimeZonesForNumber(PhoneNumber $number)
    {
        $numberType = $this->phoneUtil->getNumberType($number);

        if ($numberType === PhoneNumberType::UNKNOWN) {
            return $this->unknownTimeZoneList;
        }

        if (!PhoneNumberUtil::getInstance()->isNumberGeographical($numberType, $number->getCountryCode())) {
            return $this->getCountryLevelTimeZonesforNumber($number);
        }

        return $this->getTimeZonesForGeographicalNumber($number);
    }

    /**
     * Returns the list of time zones corresponding to the country calling code of {@code number}.
     *
     * @param $number PhoneNumber the phone number to look up
     * @return array the list of corresponding time zones or a single element list with the default
     *     unknown time zone if no other time zone was found
     */
    protected function getCountryLevelTimeZonesforNumber(PhoneNumber $number)
    {
        $timezones = $this->prefixTimeZonesMap->lookupCountryLevelTimeZonesForNumber($number);
        return (\count($timezones) == 0) ? $this->unknownTimeZoneList : $timezones;
    }

    /**
     * Returns a list of time zones to which a phone number belongs.
     *
     * <p>This method assumes the validity of the number passed in has already been checked, and that
     * the number is geo-localizable. We consider fixed-line and mobile numbers possible candidates
     * for geo-localization.
     *
     * @param $number PhoneNumber a valid phone number for which we want to get the time zones to which it belongs
     * @return array a list of the corresponding time zones or a single element list with the default
     *     unknown time zone if no other time zone was found or if the number was invalid
     */
    public function getTimeZonesForGeographicalNumber(PhoneNumber $number)
    {
        return $this->getTimeZonesForGeocodableNumber($number);
    }

    /**
     * Returns a list of time zones to which a geocodable phone number belongs.
     *
     * @param PhoneNumber $number The phone number for which we want to get the time zones to which it belongs
     * @return array the list of correspondiing time zones or a single element list with the default
     * unknown timezone if no other time zone was found or if the number was invalid
     */
    protected function getTimeZonesForGeocodableNumber(PhoneNumber $number)
    {
        $timezones = $this->prefixTimeZonesMap->lookupTimeZonesForNumber($number);
        return (\count($timezones) == 0) ? $this->unknownTimeZoneList : $timezones;
    }
}
