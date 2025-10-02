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
class PhoneNumberMetadata_FI extends PhoneMetadata
{
    protected const ID = 'FI';
    protected const COUNTRY_CODE = 358;
    protected const LEADING_DIGITS = '1[03-79]|[2-9]';
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00|99(?:[01469]|5(?:[14]1|3[23]|5[59]|77|88|9[09]))';
    protected ?string $preferredInternationalPrefix = '00';
    protected bool $mainCountryForCode = true;
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-35689]\d{4}|7\d{10,11}|(?:[124-7]\d|3[0-46-9])\d{8}|[1-9]\d{5,8}')
            ->setPossibleLength([5, 6, 7, 8, 9, 10, 11, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4946\d{2,6}|(?:4[0-8]|50)\d{4,8}')
            ->setExampleNumber('412345678')
            ->setPossibleLength([6, 7, 8, 9, 10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[67]00\d{5,6}')
            ->setExampleNumber('600123456')
            ->setPossibleLength([8, 9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1[3-7][1-8]\d{3,6}|(?:19[1-8]|[23568][1-8]\d|9(?:00|[1-8]\d))\d{2,6}')
            ->setExampleNumber('131234567')
            ->setPossibleLength([5, 6, 7, 8, 9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{5})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['75[12]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['20[2-59]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{6})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['11'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:[1-3]0|[68])0|70[07-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4,8})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[14]|2[09]|50|7[135]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6,10})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4,9})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:19|[2568])[1-8]|3(?:0[1-9]|[1-9])|9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{4,6}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([7, 8, 9]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('20\d{4,8}|60[12]\d{5,6}|7(?:099\d{4,5}|5[03-9]\d{3,7})|20[2-59]\d\d|(?:606|7(?:0[78]|1|3\d))\d{7}|(?:10|29|3[09]|70[1-5]\d)\d{4,8}')
            ->setExampleNumber('10112345');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('20(?:2[023]|9[89])\d{1,6}|(?:60[12]\d|7099)\d{4,5}|(?:606|7(?:0[78]|1|3\d))\d{7}|(?:[1-3]00|7(?:0[1-5]\d\d|5[03-9]))\d{3,7}');
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{5})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['20[2-59]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:[1-3]0|[68])0|70[07-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4,8})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[14]|2[09]|50|7[135]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6,10})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4,9})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:19|[2568])[1-8]|3(?:0[1-9]|[1-9])|9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
