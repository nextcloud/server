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
class ShortNumberMetadata_WS extends PhoneMetadata
{
    protected const ID = 'WS';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[19]\d\d')
            ->setPossibleLength([3]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:11|9[4-69])')
            ->setExampleNumber('911');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:11|9[4-69])')
            ->setExampleNumber('911');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[12]|2[0-6]|[39]0)|9(?:11|9[4-79])')
            ->setExampleNumber('111');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('12[0-6]')
            ->setExampleNumber('120');
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
