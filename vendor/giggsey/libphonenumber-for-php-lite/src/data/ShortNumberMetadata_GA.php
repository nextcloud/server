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
class ShortNumberMetadata_GA extends PhoneMetadata
{
    protected const ID = 'GA';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d(?:\d{2})?')
            ->setPossibleLength([2, 4]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('18|1(?:3\d|73)\d')
            ->setExampleNumber('18');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:3\d\d|730|8)')
            ->setExampleNumber('18');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:3\d\d|730|8)')
            ->setExampleNumber('18');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
