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
class ShortNumberMetadata_GH extends PhoneMetadata
{
    protected const ID = 'GH';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[14589]\d{2,4}')
            ->setPossibleLength([3, 4, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('19[1-3]|999')
            ->setExampleNumber('191')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('19[1-3]|999')
            ->setExampleNumber('191')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('19[1-3]|40404|(?:54|83)00|999')
            ->setExampleNumber('191');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('404\d\d|(?:54|83)0\d')
            ->setExampleNumber('5400')
            ->setPossibleLength([4, 5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('404\d\d|(?:54|83)0\d')
            ->setExampleNumber('5400')
            ->setPossibleLength([4, 5]);
    }
}
