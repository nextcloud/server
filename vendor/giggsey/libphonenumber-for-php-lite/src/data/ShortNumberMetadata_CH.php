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
class ShortNumberMetadata_CH extends PhoneMetadata
{
    protected const ID = 'CH';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-9]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:14|8[0-2589])\d|543|83111')
            ->setExampleNumber('543')
            ->setPossibleLength([3, 4, 5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:[278]|6\d{3})|4[47])|5200')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 4, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[278]|44)')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[78]\d\d|1(?:[278]|45|6(?:000|111))|4(?:[03-57]|1[0145])|6(?:00|[1-46])|8(?:02|1[189]|[25]0|7|8[08]|99))|[2-9]\d{2,4}')
            ->setExampleNumber('112');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:4[035]|6[1-46])|1(?:41|60)\d')
            ->setExampleNumber('140')
            ->setPossibleLength([3, 4]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5(?:200|35)')
            ->setExampleNumber('535')
            ->setPossibleLength([3, 4]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-9]\d{2,4}')
            ->setExampleNumber('200')
            ->setPossibleLength([3, 4, 5]);
    }
}
