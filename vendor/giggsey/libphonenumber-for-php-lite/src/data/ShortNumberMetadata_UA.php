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
class ShortNumberMetadata_UA extends PhoneMetadata
{
    protected const ID = 'UA';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[189]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[1-3]|1(?:2|6\d{3}))')
            ->setExampleNumber('101')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[1-3]|12)')
            ->setExampleNumber('101')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[1-49]|1(?:2|6(?:000|1(?:11|23))|8\d\d?)|(?:[278]|5\d)\d)|[89]00\d\d?|151|1(?:06|4\d|6)\d\d')
            ->setExampleNumber('101');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:118|[89]00)\d\d?')
            ->setExampleNumber('1180')
            ->setPossibleLength([4, 5]);
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
