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
class ShortNumberMetadata_RO extends PhoneMetadata
{
    protected const ID = 'RO';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[18]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:18[39]|[24])|8[48])\d\d')
            ->setExampleNumber('1200')
            ->setPossibleLength([4, 6]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11(?:2|6\d{3})')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:2|6(?:000|1(?:11|23))|8(?:(?:01|8[18])1|119|[23]00|932))|[24]\d\d|9(?:0(?:00|19)|1[19]|21|3[02]|5[178]))|8[48]\d\d')
            ->setExampleNumber('112');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1[24]|8[48])\d\d')
            ->setExampleNumber('1200')
            ->setPossibleLength([4]);
    }
}
