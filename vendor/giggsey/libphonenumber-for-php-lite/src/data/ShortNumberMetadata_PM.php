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
class ShortNumberMetadata_PM extends PhoneMetadata
{
    protected const ID = 'PM';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[13]\d(?:\d\d(?:\d{2})?)?')
            ->setPossibleLength([2, 4, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3[2469]\d\d')
            ->setExampleNumber('3200')
            ->setPossibleLength([4]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[578]|3(?:0\d|1[689])\d')
            ->setExampleNumber('15')
            ->setPossibleLength([2, 4]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[578]')
            ->setExampleNumber('15')
            ->setPossibleLength([2]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[578]|31(?:03|[689]\d)|(?:118[02-9]|3[02469])\d\d')
            ->setExampleNumber('15');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('118\d{3}')
            ->setExampleNumber('118000')
            ->setPossibleLength([6]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('310\d')
            ->setExampleNumber('3100')
            ->setPossibleLength([4]);
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
