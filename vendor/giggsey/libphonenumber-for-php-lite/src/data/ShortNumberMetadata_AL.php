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
class ShortNumberMetadata_AL extends PhoneMetadata
{
    protected const ID = 'AL';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[15]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5\d{4}')
            ->setExampleNumber('50000')
            ->setPossibleLength([5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:2|6[01]\d\d)|2[7-9]|3[15]|41)')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|2[7-9])')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:6(?:000|1(?:06|11|23))|8\d\d)|65\d|89[12])|5\d{4}|1(?:[1349]\d|2[2-9])')
            ->setExampleNumber('110');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('123')
            ->setExampleNumber('123')
            ->setPossibleLength([3]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('131|5\d{4}')
            ->setExampleNumber('131')
            ->setPossibleLength([3, 5]);
    }
}
