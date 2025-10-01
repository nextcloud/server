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
class ShortNumberMetadata_FR extends PhoneMetadata
{
    protected const ID = 'FR';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-8]\d{1,5}')
            ->setPossibleLength([2, 3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:0|18\d)|366|[4-8]\d\d)\d\d|3[2-9]\d\d')
            ->setExampleNumber('1000')
            ->setPossibleLength([4, 5, 6]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[02459]|[578]|9[167])|224|(?:3370|74)0|(?:116\d|3[01])\d\d')
            ->setExampleNumber('15');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|[578])')
            ->setExampleNumber('15')
            ->setPossibleLength([2, 3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0\d\d|1(?:[02459]|6(?:000|111)|8\d{3})|[578]|9[167])|2(?:0(?:00|2)0|24)|[3-8]\d{4}|3\d{3}|6(?:1[14]|34)|7(?:0[06]|22|40)')
            ->setExampleNumber('15');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('202\d|6(?:1[14]|34)|70[06]')
            ->setExampleNumber('611')
            ->setPossibleLength([3, 4]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('118777|224|6(?:1[14]|34)|7(?:0[06]|22|40)|20(?:0\d|2)\d')
            ->setExampleNumber('224')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('114|[3-8]\d{4}')
            ->setExampleNumber('114')
            ->setPossibleLength([3, 5]);
    }
}
