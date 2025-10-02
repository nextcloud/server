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
class ShortNumberMetadata_KN extends PhoneMetadata
{
    protected const ID = 'KN';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[39]\d\d')
            ->setPossibleLength([3]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('333|9(?:11|88|99)')
            ->setExampleNumber('333');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('333|9(?:11|99)')
            ->setExampleNumber('333');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('333|9(?:11|88|99)')
            ->setExampleNumber('333');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
