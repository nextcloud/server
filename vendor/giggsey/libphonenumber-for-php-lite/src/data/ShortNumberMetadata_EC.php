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
class ShortNumberMetadata_EC extends PhoneMetadata
{
    protected const ID = 'EC';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[19]\d\d')
            ->setPossibleLength([3]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[12]|12)|911')
            ->setExampleNumber('101');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[12]|12)|911')
            ->setExampleNumber('101');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[12]|12)|911')
            ->setExampleNumber('101');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
