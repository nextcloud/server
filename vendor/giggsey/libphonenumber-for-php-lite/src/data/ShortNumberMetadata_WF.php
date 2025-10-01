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
class ShortNumberMetadata_WF extends PhoneMetadata
{
    protected const ID = 'WF';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d')
            ->setPossibleLength([2]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[578]')
            ->setExampleNumber('15');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[578]')
            ->setExampleNumber('15');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[578]')
            ->setExampleNumber('15');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
