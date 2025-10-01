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
class ShortNumberMetadata_OM extends PhoneMetadata
{
    protected const ID = 'OM';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[19]\d{3}')
            ->setPossibleLength([4]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1444|999\d')
            ->setExampleNumber('1444');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1444|9999')
            ->setExampleNumber('1444');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:111|222|4(?:4[0-5]|50|66|7[7-9])|51[0-8])|9999|1(?:2[3-5]|3[0-2]|50)\d')
            ->setExampleNumber('1111');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
