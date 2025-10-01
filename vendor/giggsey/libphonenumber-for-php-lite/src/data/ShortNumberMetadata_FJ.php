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
class ShortNumberMetadata_FJ extends PhoneMetadata
{
    protected const ID = 'FJ';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[0-579]\d(?:\d(?:\d{2})?)?')
            ->setPossibleLength([2, 3, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('91[17]')
            ->setExampleNumber('911')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('91[17]')
            ->setExampleNumber('911')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:1[34]|8[1-4])|1(?:0[1-3]|[25]9)|2[289]|30|40404|91[137]|[45]4|75')
            ->setExampleNumber('22');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('404\d\d')
            ->setExampleNumber('40400')
            ->setPossibleLength([5]);
    }
}
