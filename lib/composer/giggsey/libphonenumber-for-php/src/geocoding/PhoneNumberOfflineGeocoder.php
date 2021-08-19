<?php

namespace libphonenumber\geocoding;

use Giggsey\Locale\Locale;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\prefixmapper\PrefixFileReader;

class PhoneNumberOfflineGeocoder
{
    const MAPPING_DATA_DIRECTORY = '/data';
    /**
     * @var PhoneNumberOfflineGeocoder
     */
    protected static $instance;
    /**
     * @var PhoneNumberUtil
     */
    protected $phoneUtil;
    /**
     * @var PrefixFileReader
     */
    protected $prefixFileReader;

    /**
     * PhoneNumberOfflineGeocoder constructor.
     * @param string $phonePrefixDataDirectory
     */
    protected function __construct($phonePrefixDataDirectory)
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();

        $this->prefixFileReader = new PrefixFileReader(__DIR__ . DIRECTORY_SEPARATOR . $phonePrefixDataDirectory);
    }

    /**
     * Gets a PhoneNumberOfflineGeocoder instance to carry out international phone number geocoding.
     *
     * <p>The PhoneNumberOfflineGeocoder is implemented as a singleton. Therefore, calling this method
     * multiple times will only result in one instance being created.
     *
     * @param string $mappingDir (Optional) Mapping Data Directory
     * @return PhoneNumberOfflineGeocoder
     */
    public static function getInstance($mappingDir = self::MAPPING_DATA_DIRECTORY)
    {
        if (static::$instance === null) {
            static::$instance = new static($mappingDir);
        }

        return static::$instance;
    }

    public static function resetInstance()
    {
        static::$instance = null;
    }

    /**
     * As per getDescriptionForValidNumber, but explicitly checks the validity of the number
     * passed in.
     *
     *
     * @see getDescriptionForValidNumber
     * @param PhoneNumber $number a valid phone number for which we want to get a text description
     * @param string $locale the language code for which the description should be written
     * @param string $userRegion the region code for a given user. This region will be omitted from the
     *     description if the phone number comes from this region. It is a two-letter uppercase CLDR region
     *     code.
     * @return string a text description for the given language code for the given phone number, or empty
     *     string if the number passed in is invalid
     */
    public function getDescriptionForNumber(PhoneNumber $number, $locale, $userRegion = null)
    {
        $numberType = $this->phoneUtil->getNumberType($number);

        if ($numberType === PhoneNumberType::UNKNOWN) {
            return '';
        }

        if (!$this->phoneUtil->isNumberGeographical($numberType, $number->getCountryCode())) {
            return $this->getCountryNameForNumber($number, $locale);
        }

        return $this->getDescriptionForValidNumber($number, $locale, $userRegion);
    }

    /**
     * Returns the customary display name in the given language for the given territory the phone
     * number is from. If it could be from many territories, nothing is returned.
     *
     * @param PhoneNumber $number
     * @param string $locale
     * @return string
     */
    protected function getCountryNameForNumber(PhoneNumber $number, $locale)
    {
        $regionCodes = $this->phoneUtil->getRegionCodesForCountryCode($number->getCountryCode());

        if (\count($regionCodes) === 1) {
            return $this->getRegionDisplayName($regionCodes[0], $locale);
        }

        $regionWhereNumberIsValid = 'ZZ';
        foreach ($regionCodes as $regionCode) {
            if ($this->phoneUtil->isValidNumberForRegion($number, $regionCode)) {
                // If the number has already been found valid for one region, then we don't know which
                // region it belongs to so we return nothing.
                if ($regionWhereNumberIsValid !== 'ZZ') {
                    return '';
                }
                $regionWhereNumberIsValid = $regionCode;
            }
        }

        return $this->getRegionDisplayName($regionWhereNumberIsValid, $locale);
    }

    /**
     * Returns the customary display name in the given language for the given region.
     *
     * @param $regionCode
     * @param $locale
     * @return string
     */
    protected function getRegionDisplayName($regionCode, $locale)
    {
        if ($regionCode === null || $regionCode == 'ZZ' || $regionCode === PhoneNumberUtil::REGION_CODE_FOR_NON_GEO_ENTITY) {
            return '';
        }

        return Locale::getDisplayRegion(
            '-' . $regionCode,
            $locale
        );
    }

    /**
     * Returns a text description for the given phone number, in the language provided. The
     * description might consist of the name of the country where the phone number is from, or the
     * name of the geographical area the phone number is from if more detailed information is
     * available.
     *
     * <p>This method assumes the validity of the number passed in has already been checked, and that
     * the number is suitable for geocoding. We consider fixed-line and mobile numbers possible
     * candidates for geocoding.
     *
     * <p>If $userRegion is set, we also consider the region of the user. If the phone number is from
     * the same region as the user, only a lower-level description will be returned, if one exists.
     * Otherwise, the phone number's region will be returned, with optionally some more detailed
     * information.
     *
     * <p>For example, for a user from the region "US" (United States), we would show "Mountain View,
     * CA" for a particular number, omitting the United States from the description. For a user from
     * the United Kingdom (region "GB"), for the same number we may show "Mountain View, CA, United
     * States" or even just "United States".
     *
     * @param PhoneNumber $number a valid phone number for which we want to get a text description
     * @param string $locale the language code for which the description should be written
     * @param string $userRegion the region code for a given user. This region will be omitted from the
     *     description if the phone number comes from this region. It is a two-letter upper-case CLDR
     *     region code.
     * @return string a text description for the given language code for the given phone number, or an
     *     empty string if the number could come from multiple countries, or the country code is
     *     in fact invalid
     */
    public function getDescriptionForValidNumber(PhoneNumber $number, $locale, $userRegion = null)
    {
        // If the user region matches the number's region, then we just show the lower-level
        // description, if one exists - if no description exists, we will show the region(country) name
        // for the number.
        $regionCode = $this->phoneUtil->getRegionCodeForNumber($number);
        if ($userRegion == null || $userRegion == $regionCode) {
            $languageStr = Locale::getPrimaryLanguage($locale);
            $scriptStr = '';
            $regionStr = Locale::getRegion($locale);

            $mobileToken = PhoneNumberUtil::getCountryMobileToken($number->getCountryCode());
            $nationalNumber = $this->phoneUtil->getNationalSignificantNumber($number);
            if ($mobileToken !== '' && (!\strncmp($nationalNumber, $mobileToken, \strlen($mobileToken)))) {
                // In some countries, eg. Argentina, mobile numbers have a mobile token before the national
                // destination code, this should be removed before geocoding.
                $nationalNumber = \substr($nationalNumber, \strlen($mobileToken));
                $region = $this->phoneUtil->getRegionCodeForCountryCode($number->getCountryCode());
                try {
                    $copiedNumber = $this->phoneUtil->parse($nationalNumber, $region);
                } catch (NumberParseException $e) {
                    // If this happens, just reuse what we had.
                    $copiedNumber = $number;
                }
                $areaDescription = $this->prefixFileReader->getDescriptionForNumber($copiedNumber, $languageStr, $scriptStr, $regionStr);
            } else {
                $areaDescription = $this->prefixFileReader->getDescriptionForNumber($number, $languageStr, $scriptStr, $regionStr);
            }

            return (\strlen($areaDescription) > 0) ? $areaDescription : $this->getCountryNameForNumber($number, $locale);
        }
        // Otherwise, we just show the region(country) name for now.
        return $this->getRegionDisplayName($regionCode, $locale);
        // TODO: Concatenate the lower-level and country-name information in an appropriate
        // way for each language.
    }
}
