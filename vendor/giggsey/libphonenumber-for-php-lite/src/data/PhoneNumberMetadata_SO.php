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
class PhoneNumberMetadata_SO extends PhoneMetadata
{
    protected const ID = 'SO';
    protected const COUNTRY_CODE = 252;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[346-9]\d{8}|[12679]\d{7}|[1-5]\d{6}|[1348]\d{5}')
            ->setPossibleLength([6, 7, 8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:(?:15|(?:3[59]|4[89]|6\d|7[679]|8[08])\d|9(?:0\d|[2-9]))\d|2(?:4\d|8))\d{5}|(?:[67]\d\d|904)\d{5}')
            ->setExampleNumber('71123456')
            ->setPossibleLength([7, 8, 9]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1\d|2[0-79]|3[0-46-8]|4[0-7]|5[57-9])\d{5}|(?:[134]\d|8[125])\d{4}')
            ->setExampleNumber('4012345')
            ->setPossibleLength([6, 7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['8[125]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{6})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['[134]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[15]|2[0-79]|3[0-46-8]|4[0-7]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:2|90)4|[67]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[348]|64|79|90'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5,7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['1|28|6[0-35-9]|7[67]|9[2-9]'])
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
