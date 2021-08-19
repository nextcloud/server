<?php

namespace libphonenumber\prefixmapper;

/**
 * A utility which knows the data files that are available for the phone prefix mappers to use.
 * The data files contain mappings from phone number prefixes to text descriptions, and are
 * organized by country calling code and language that the text descriptions are in.
 *
 * Class MappingFileProvider
 * @package libphonenumber\prefixmapper
 * @internal
 */
class MappingFileProvider
{
    protected $map;

    public function __construct($map)
    {
        $this->map = $map;
    }

    public function getFileName($countryCallingCode, $language, $script, $region)
    {
        if (strlen($language) == 0) {
            return '';
        }

        if ($language === 'zh' && ($region == 'TW' || $region == 'HK' || $region == 'MO')) {
            $language = 'zh_Hant';
        }

        // Loop through the $countryCallingCode and load the prefix
        $prefixLength = strlen($countryCallingCode);

        for ($i = $prefixLength; $i > 0; $i--) {
            $prefix = substr($countryCallingCode, 0, $i);
            if ($this->inMap($language, $prefix)) {
                return $language . DIRECTORY_SEPARATOR . $prefix . '.php';
            }
        }

        return '';
    }

    protected function inMap($language, $countryCallingCode)
    {
        return (array_key_exists($language, $this->map) && in_array($countryCallingCode, $this->map[$language]));
    }
}
