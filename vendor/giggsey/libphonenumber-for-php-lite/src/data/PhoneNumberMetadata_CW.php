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
class PhoneNumberMetadata_CW extends PhoneMetadata
{
    protected const ID = 'CW';
    protected const COUNTRY_CODE = 599;
    protected const LEADING_DIGITS = '[69]';

    protected ?string $internationalPrefix = '00';
    protected bool $mainCountryForCode = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[34]1|60|(?:7|9\d)\d)\d{5}')
            ->setPossibleLength([7, 8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('953[01]\d{4}|9(?:5[12467]|6[5-9])\d{5}')
            ->setExampleNumber('95181234');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:4(?:3[0-5]|4[14]|6\d)|50\d|7(?:2[014]|3[02-9]|4[4-9]|6[357]|77|8[7-9])|8(?:3[39]|[46]\d|7[01]|8[57-9]))\d{4}')
            ->setExampleNumber('94351234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[3467]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9[4-8]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('60[0-2]\d{4}')
            ->setExampleNumber('6001234')
            ->setPossibleLength([7]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('955\d{5}')
            ->setExampleNumber('95581234')
            ->setPossibleLength([8]);
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
