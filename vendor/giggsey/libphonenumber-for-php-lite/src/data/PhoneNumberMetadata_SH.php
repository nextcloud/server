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
class PhoneNumberMetadata_SH extends PhoneMetadata
{
    protected const ID = 'SH';
    protected const COUNTRY_CODE = 290;
    protected const LEADING_DIGITS = '[256]';

    protected ?string $internationalPrefix = '00';
    protected bool $mainCountryForCode = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[256]\d|8)\d{3}')
            ->setPossibleLength([4, 5]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[56]\d{4}')
            ->setExampleNumber('51234')
            ->setPossibleLength([5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:[0-57-9]\d|6[4-9])\d\d')
            ->setExampleNumber('22158');
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('262\d\d')
            ->setExampleNumber('26212')
            ->setPossibleLength([5]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
