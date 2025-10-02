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
class ShortNumberMetadata_SN extends PhoneMetadata
{
    protected const ID = 'SN';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[12]\d{1,5}')
            ->setPossibleLength([2, 3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:0[246]|[468])\d{3}')
            ->setExampleNumber('24000')
            ->setPossibleLength([5, 6]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:515|[78])|2(?:00|1)\d{3}')
            ->setExampleNumber('17')
            ->setPossibleLength([2, 4, 5, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[78]')
            ->setExampleNumber('17')
            ->setPossibleLength([2]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[69]|(?:[246]\d|51)\d)|2(?:0[0-246]|[12468])\d{3}|1[278]')
            ->setExampleNumber('12');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:01|2)\d{3}')
            ->setExampleNumber('22000')
            ->setPossibleLength([5, 6]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[46]\d\d')
            ->setExampleNumber('1400')
            ->setPossibleLength([4]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2[468]\d{3}')
            ->setExampleNumber('24000')
            ->setPossibleLength([5]);
    }
}
