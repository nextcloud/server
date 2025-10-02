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
class ShortNumberMetadata_SK extends PhoneMetadata
{
    protected const ID = 'SK';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:2|6\d{3})|5[058])')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|5[058])')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:2|6(?:000|111)|8[0-8])|[248]\d{3}|5[0589])')
            ->setExampleNumber('112');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
