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
class ShortNumberMetadata_NO extends PhoneMetadata
{
    protected const ID = 'NO';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[01]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11(?:[023]|6\d{3})')
            ->setExampleNumber('110')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[023]')
            ->setExampleNumber('110')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('04\d{3}|1(?:1(?:[0239]|61(?:1[17]|23))|2[048]|4(?:12|[59])|7[57]|8\d\d|90)')
            ->setExampleNumber('110');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('04\d{3}')
            ->setExampleNumber('04000')
            ->setPossibleLength([5]);
    }
}
