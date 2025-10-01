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
class ShortNumberMetadata_CU extends PhoneMetadata
{
    protected const ID = 'CU';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[12]\d\d(?:\d{3,4})?')
            ->setPossibleLength([3, 6, 7]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('10[4-7]|(?:116|204\d)\d{3}')
            ->setExampleNumber('104');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('10[4-6]')
            ->setExampleNumber('104')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[4-7]|1(?:6111|8)|40)|2045252')
            ->setExampleNumber('104');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
