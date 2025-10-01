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
class ShortNumberMetadata_AR extends PhoneMetadata
{
    protected const ID = 'AR';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[01389]\d{1,4}')
            ->setPossibleLength([2, 3, 4, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('000|1(?:0[0-35-7]|1[0245]|2[015]|3[47]|4[478]|9)|911')
            ->setExampleNumber('19')
            ->setPossibleLength([2, 3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('10[017]|911')
            ->setExampleNumber('100')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('000|1(?:0[0-35-7]|1[02-5]|2[015]|3[47]|4[478]|9)|3372|89338|911')
            ->setExampleNumber('19');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('893\d\d')
            ->setExampleNumber('89300')
            ->setPossibleLength([5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:337|893\d)\d')
            ->setExampleNumber('3370')
            ->setPossibleLength([4, 5]);
    }
}
