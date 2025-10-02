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
class ShortNumberMetadata_GB extends PhoneMetadata
{
    protected const ID = 'GB';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-46-9]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:05|1(?:[29]|6\d{3})|7[56]\d|8000)|2(?:20\d|48)|4444|999')
            ->setExampleNumber('105');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|999')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[015]|1(?:[129]|6(?:000|1(?:11|23))|8\d{3})|2(?:[1-3]|50)|33|4(?:1|7\d)|571|7(?:0\d|[56]0)|800\d|9[15])|2(?:0202|1300|2(?:02|11)|3(?:02|336|45)|4(?:25|8))|3[13]3|4(?:0[02]|35[01]|44[45]|5\d)|(?:[68]\d|7[089])\d{3}|15\d|2[02]2|650|789|9(?:01|99)')
            ->setExampleNumber('100');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:(?:25|7[56])\d|571)|2(?:02(?:\d{2})?|[13]3\d\d|48)|4444|901')
            ->setExampleNumber('202')
            ->setPossibleLength([3, 4, 5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:125|2(?:020|13\d)|(?:7[089]|8[01])\d\d)\d')
            ->setExampleNumber('1250')
            ->setPossibleLength([4, 5]);
    }
}
