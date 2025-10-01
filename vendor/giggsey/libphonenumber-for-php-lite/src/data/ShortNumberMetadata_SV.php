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
class ShortNumberMetadata_SV extends PhoneMetadata
{
    protected const ID = 'SV';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[149]\d\d(?:\d{2,3})?')
            ->setPossibleLength([3, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('116\d{3}|911')
            ->setExampleNumber('911')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('91[13]')
            ->setExampleNumber('911')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:2|6111)|2[136-8]|3[0-6]|9[05])|40404|9(?:1\d|29)')
            ->setExampleNumber('112');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('404\d\d')
            ->setExampleNumber('40400')
            ->setPossibleLength([5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('404\d\d')
            ->setExampleNumber('40400')
            ->setPossibleLength([5]);
    }
}
