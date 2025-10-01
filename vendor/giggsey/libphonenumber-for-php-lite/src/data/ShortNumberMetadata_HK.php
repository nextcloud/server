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
class ShortNumberMetadata_HK extends PhoneMetadata
{
    protected const ID = 'HK';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[19]\d{2,6}')
            ->setPossibleLength([3, 4, 5, 6, 7]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|99[29]')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|99[29]')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0(?:(?:[0136]\d|2[14])\d{0,3}|8[138])|12|2(?:[0-3]\d{0,4}|(?:58|8[13])\d{0,3})|7(?:[135-9]\d{0,4}|219\d{0,2})|8(?:0(?:(?:[13]|60\d)\d|8)|1(?:0\d|[2-8])|2(?:0[5-9]|(?:18|2)2|3|8[128])|(?:(?:3[0-689]\d|7(?:2[1-389]|8[0235-9]|93))\d|8)\d|50[138]|6(?:1(?:11|86)|8)))|99[29]|10[0139]')
            ->setExampleNumber('100');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('109|1(?:08|85\d)\d')
            ->setExampleNumber('109')
            ->setPossibleLength([3, 4, 5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('992')
            ->setExampleNumber('992')
            ->setPossibleLength([3]);
    }
}
