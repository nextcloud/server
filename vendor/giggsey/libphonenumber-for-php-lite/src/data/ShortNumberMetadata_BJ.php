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
class ShortNumberMetadata_BJ extends PhoneMetadata
{
    protected const ID = 'BJ';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[17]\d{2,3}')
            ->setPossibleLength([3, 4]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[246-8]|3[68]|6[06])|7[3-5]\d\d')
            ->setExampleNumber('112');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[246-8]')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:05|1[24-8]|2[02-5]|3[126-8]|5[05]|6[06]|89)|7[0-5]\d\d')
            ->setExampleNumber('105');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('12[02-5]')
            ->setExampleNumber('120')
            ->setPossibleLength([3]);
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
