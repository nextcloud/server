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
class ShortNumberMetadata_KE extends PhoneMetadata
{
    protected const ID = 'KE';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-9]\d{2,4}')
            ->setPossibleLength([3, 4, 5]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('909\d\d')
            ->setExampleNumber('90900')
            ->setPossibleLength([5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:[246]|9\d)|5(?:01|2[127]|6[26]\d))|999')
            ->setExampleNumber('112');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[24]|999')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0(?:[07-9]|1[0-25]|400)|1(?:[024-6]|9[0-579])|2[1-3]|3[01]|4[14]|5(?:[01][01]|2[0-24-79]|33|4[05]|5[59]|6(?:00|29|6[67]))|(?:6[035]\d|[78])\d|9(?:[02-9]\d\d|19))|(?:(?:2[0-79]|[37][0-29]|4[0-4]|6[2357]|8\d)\d|5(?:[0-7]\d|99))\d\d|9(?:09\d\d|99)|8988')
            ->setExampleNumber('100');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:(?:04|6[35])\d\d|3[01]|4[14]|5(?:1\d|2[25]))|(?:(?:2[0-79]|[37][0-29]|4[0-4]|6[2357]|8\d)\d|5(?:[0-7]\d|99)|909)\d\d|898\d')
            ->setExampleNumber('130');
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:(?:04|6[035])\d\d|4[14]|5(?:01|55|6[26]\d))|40404|8988|909\d\d')
            ->setExampleNumber('141');
    }
}
