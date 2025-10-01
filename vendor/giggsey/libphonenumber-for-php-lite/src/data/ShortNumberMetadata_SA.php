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
class ShortNumberMetadata_SA extends PhoneMetadata
{
    protected const ID = 'SA';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[19]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11(?:2|6\d{3})|9(?:11|37|9[7-9])')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|9(?:11|9[79])')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:00|2|6111)|410|9(?:00|1[89]|9(?:099|22|9[0-3])))|9(?:0[24-79]|11|3[379]|40|66|8[5-9]|9[02-9])')
            ->setExampleNumber('112');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('141\d')
            ->setExampleNumber('1410')
            ->setPossibleLength([4]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:10|41)\d|90[24679]')
            ->setExampleNumber('902')
            ->setPossibleLength([3, 4]);
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
