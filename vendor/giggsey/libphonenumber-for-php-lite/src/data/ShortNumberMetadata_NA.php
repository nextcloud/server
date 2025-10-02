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
class ShortNumberMetadata_NA extends PhoneMetadata
{
    protected const ID = 'NA';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[19]\d{2,4}')
            ->setPossibleLength([3, 4, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('10111')
            ->setExampleNumber('10111')
            ->setPossibleLength([5]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('10111')
            ->setExampleNumber('10111')
            ->setPossibleLength([5]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:10|93)111|(?:1\d|9)\d\d')
            ->setExampleNumber('900');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
