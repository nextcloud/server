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
class ShortNumberMetadata_ID extends PhoneMetadata
{
    protected const ID = 'ID';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[178]\d\d(?:\d{2,3})?')
            ->setPossibleLength([3, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[02389]')
            ->setExampleNumber('110')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[02389]')
            ->setExampleNumber('110')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[02389]|40\d\d|50264)|71400|89887')
            ->setExampleNumber('110');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:714|898)\d\d')
            ->setExampleNumber('71400')
            ->setPossibleLength([5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('714\d\d')
            ->setExampleNumber('71400')
            ->setPossibleLength([5]);
    }
}
