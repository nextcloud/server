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
class ShortNumberMetadata_NZ extends PhoneMetadata
{
    protected const ID = 'NZ';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('\d{3,4}')
            ->setPossibleLength([3, 4]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('018')
            ->setExampleNumber('018')
            ->setPossibleLength([3]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('111')
            ->setExampleNumber('111')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('111')
            ->setExampleNumber('111')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('018|1(?:(?:1|37)1|(?:23|94)4|7[03]7)|[2-57-9]\d{2,3}|6(?:161|26[0-3]|742)')
            ->setExampleNumber('018');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('018|(?:1(?:23|37|7[03]|94)|6(?:[12]6|74))\d|[2-57-9]\d{2,3}')
            ->setExampleNumber('018');
    }
}
