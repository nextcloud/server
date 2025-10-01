<?php

/**
 * libphonenumber-for-php-lite data file
 * This file has been @generated from libphonenumber data
 * Do not modify!
 * @internal
 */

declare(strict_types=1);

namespace libphonenumber\data;

use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumberDesc;

/**
 * @internal
 */
class ShortNumberMetadata_BR extends PhoneMetadata
{
    protected const ID = 'BR';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-69]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:00|12|28|8[015]|9[0-47-9])|4(?:57|82\d)|911')
            ->setExampleNumber('100')
            ->setPossibleLength([3, 4]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|28|9[023])|911')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0(?:[02]|3(?:1[2-579]|2[13-9]|3[124-9]|4[1-3578]|5[1-468]|6[139]|8[149]|9[168])|5[0-35-9]|6(?:0|1[0-35-8]?|2[0145]|3[0137]?|4[37-9]?|5[0-35]|6[016]?|7[137]?|8[5-8]|9[1359]))|1[25-8]|2[357-9]|3[024-68]|4[12568]|5\d|6[0-8]|8[015]|9[0-47-9])|2(?:7(?:330|878)|85959?)|(?:32|91)1|4(?:0404?|57|828)|55555|6(?:0\d{4}|10000)|(?:133|411)[12]')
            ->setExampleNumber('100');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('102|273\d\d|321')
            ->setExampleNumber('102')
            ->setPossibleLength([3, 5]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('151|(?:278|555)\d\d|4(?:04\d\d?|11\d|57)')
            ->setExampleNumber('151')
            ->setPossibleLength([3, 4, 5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('285\d{2,3}|321|40404|(?:27[38]\d|482)\d|6(?:0\d|10)\d{3}')
            ->setExampleNumber('321');
    }
}
