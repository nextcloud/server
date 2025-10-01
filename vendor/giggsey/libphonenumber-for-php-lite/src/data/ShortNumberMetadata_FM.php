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
class ShortNumberMetadata_FM extends PhoneMetadata
{
    protected const ID = 'FM';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[39]\d\d(?:\d{3})?')
            ->setPossibleLength([3, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('320\d{3}|911')
            ->setExampleNumber('911');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:32022|91)1')
            ->setExampleNumber('911');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:32022|91)1')
            ->setExampleNumber('911');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
