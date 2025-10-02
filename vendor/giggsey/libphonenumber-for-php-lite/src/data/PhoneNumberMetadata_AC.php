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
class PhoneNumberMetadata_AC extends PhoneMetadata
{
    protected const ID = 'AC';
    protected const COUNTRY_CODE = 247;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[01589]\d|[46])\d{4}')
            ->setPossibleLength([5, 6]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4\d{4}')
            ->setExampleNumber('40123')
            ->setPossibleLength([5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6[2-467]\d{3}')
            ->setExampleNumber('62889')
            ->setPossibleLength([5]);
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:0[1-9]|[1589]\d)\d{4}')
            ->setExampleNumber('542011')
            ->setPossibleLength([6]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
