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
class PhoneNumberMetadata_TJ extends PhoneMetadata
{
    protected const ID = 'TJ';
    protected const COUNTRY_CODE = 992;

    protected ?string $internationalPrefix = '810';
    protected ?string $preferredInternationalPrefix = '8~10';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[0-57-9]\d{8}')
            ->setPossibleLengthLocalOnly([3, 5, 6, 7])
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:33[03-9]|4(?:1[18]|4[02-479])|81[1-9])\d{6}|(?:[09]\d|1[0-27-9]|2[0-27]|[34]0|5[05]|7[01578]|8[078])\d{7}')
            ->setExampleNumber('917123456');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3(?:1[3-5]|2[245]|3[12]|4[24-7]|5[25]|72)|4(?:46|74|87))\d{6}')
            ->setExampleNumber('372123456')
            ->setPossibleLengthLocalOnly([3, 5, 6, 7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{6})(\d)(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['331', '3317'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['44[02-479]|[34]7'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d)(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['3(?:[1245]|3[12])'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[0-57-9]'])
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
