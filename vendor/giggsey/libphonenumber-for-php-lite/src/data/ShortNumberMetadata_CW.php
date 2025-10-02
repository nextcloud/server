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
class ShortNumberMetadata_CW extends PhoneMetadata
{
    protected const ID = 'CW';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[19]\d\d')
            ->setPossibleLength([3]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|911')
            ->setExampleNumber('112');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|911')
            ->setExampleNumber('112');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|76)|911')
            ->setExampleNumber('112');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('176')
            ->setExampleNumber('176');
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('176')
            ->setExampleNumber('176');
    }
}
