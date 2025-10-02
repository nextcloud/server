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
class ShortNumberMetadata_NC extends PhoneMetadata
{
    protected const ID = 'NC';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[135]\d{1,3}')
            ->setPossibleLength([2, 3, 4]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0(?:00|1[23]|3[0-2]|8\d)|[5-8])|363\d|577')
            ->setExampleNumber('15');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[5-8]')
            ->setExampleNumber('15')
            ->setPossibleLength([2]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0(?:0[06]|1[02-46]|20|3[0-25]|42|5[058]|77|88)|[5-8])|3631|5[6-8]\d')
            ->setExampleNumber('15');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5(?:67|88)')
            ->setExampleNumber('567')
            ->setPossibleLength([3]);
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
