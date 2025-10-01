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
class ShortNumberMetadata_RU extends PhoneMetadata
{
    protected const ID = 'RU';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[01]\d\d?')
            ->setPossibleLength([2, 3]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|(?:0|10)[1-3]')
            ->setExampleNumber('01');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|(?:0|10)[1-3]')
            ->setExampleNumber('01');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|(?:0|10)[1-4]')
            ->setExampleNumber('01');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
