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
class PhoneNumberMetadata_LY extends PhoneMetadata
{
    protected const ID = 'LY';
    protected const COUNTRY_CODE = 218;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-9]\d{8}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9[1-6]\d{7}')
            ->setExampleNumber('912345678');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:0[56]|[1-6]\d|7[124579]|8[124])|3(?:1\d|2[2356])|4(?:[17]\d|2[1-357]|5[2-4]|8[124])|5(?:[1347]\d|2[1-469]|5[13-5]|8[1-4])|6(?:[1-479]\d|5[2-57]|8[1-5])|7(?:[13]\d|2[13-79])|8(?:[124]\d|5[124]|84))\d{6}')
            ->setExampleNumber('212345678')
            ->setPossibleLengthLocalOnly([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{7})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[2-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
