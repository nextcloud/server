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
class ShortNumberMetadata_NE extends PhoneMetadata
{
    protected const ID = 'NE';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-3578]\d(?:\d(?:\d{3})?)?')
            ->setPossibleLength([2, 3, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:18|[578])|723\d{3}')
            ->setExampleNumber('15');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:18|[578])|723141')
            ->setExampleNumber('15');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[01]|1[128]|2[034]|3[013]|[46]0|55?|[78])|222|333|555|723141|888')
            ->setExampleNumber('15');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[01]|1[12]|2[034]|3[013]|[46]0|55)|222|333|555|888')
            ->setExampleNumber('100')
            ->setPossibleLength([3]);
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
