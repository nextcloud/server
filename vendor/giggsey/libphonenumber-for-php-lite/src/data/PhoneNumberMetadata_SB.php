<?php

/**
 * libphonenumber-for-php-lite data file
 * This file has been @generated from libphonenumber data
 * Do not modify!
 * @internal
 */

declare(strict_types=1);

namespace libphonenumber\data;

use libphonenumber\NumberFormat;
use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumberDesc;

/**
 * @internal
 */
class PhoneNumberMetadata_SB extends PhoneMetadata
{
    protected const ID = 'SB';
    protected const COUNTRY_CODE = 677;

    protected ?string $internationalPrefix = '0[01]';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[6-9]\d{6}|[1-6]\d{4}')
            ->setPossibleLength([5, 7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('48\d{3}|(?:(?:6[89]|7[1-9]|8[4-9])\d|9(?:1[2-9]|2[013-9]|3[0-2]|[46]\d|5[0-46-9]|7[0-689]|8[0-79]|9[0-8]))\d{4}')
            ->setExampleNumber('7421234');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1[4-79]|[23]\d|4[0-2]|5[03]|6[0-37])\d{3}')
            ->setExampleNumber('40123')
            ->setPossibleLength([5]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['6[89]|7|8[4-9]|9(?:[1-8]|9[0-8])'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[38]\d{3}')
            ->setExampleNumber('18123')
            ->setPossibleLength([5]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5[12]\d{3}')
            ->setExampleNumber('51123')
            ->setPossibleLength([5]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
