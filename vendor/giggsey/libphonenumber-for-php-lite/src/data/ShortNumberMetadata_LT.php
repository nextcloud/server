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
class ShortNumberMetadata_LT extends PhoneMetadata
{
    protected const ID = 'LT';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[01]\d(?:\d(?:\d{3})?)?')
            ->setPossibleLength([2, 3, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:11?|22?|33?)|1(?:0[1-3]|1(?:2|6111))|116(?:0\d|12)\d')
            ->setExampleNumber('01');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:11?|22?|33?)|1(?:0[1-3]|12)')
            ->setExampleNumber('01')
            ->setPossibleLength([2, 3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:11?|22?|33?)|1(?:0[1-3]|1(?:[27-9]|6(?:000|1(?:1[17]|23))))')
            ->setExampleNumber('01');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
