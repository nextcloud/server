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
class ShortNumberMetadata_ML extends PhoneMetadata
{
    protected const ID = 'ML';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[136-8]\d{1,4}')
            ->setPossibleLength([2, 3, 4, 5]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:12|800)2\d|3(?:52(?:11|2[02]|3[04-6]|99)|7574)')
            ->setExampleNumber('1220')
            ->setPossibleLength([4, 5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[578]|(?:352|67)00|7402|(?:677|744|8000)\d')
            ->setExampleNumber('15')
            ->setPossibleLength([2, 4, 5]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[578]')
            ->setExampleNumber('15')
            ->setPossibleLength([2]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:[013-9]\d|2)|2(?:1[02-469]|2[13])|[578])|350(?:35|57)|67(?:0[09]|[59]9|77|8[89])|74(?:0[02]|44|55)|800[0-2][12]|3(?:52|[67]\d)\d\d')
            ->setExampleNumber('15');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('37(?:433|575)|7400|8001\d')
            ->setExampleNumber('7400')
            ->setPossibleLength([4, 5]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3503\d|(?:3[67]\d|800)\d\d')
            ->setExampleNumber('35030')
            ->setPossibleLength([5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('374(?:0[24-9]|[1-9]\d)|7400|3(?:6\d|75)\d\d')
            ->setExampleNumber('7400')
            ->setPossibleLength([4, 5]);
    }
}
