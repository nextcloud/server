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
class PhoneNumberMetadata_NL extends PhoneMetadata
{
    protected const ID = 'NL';
    protected const COUNTRY_CODE = 31;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[124-7]\d\d|3(?:[02-9]\d|1[0-8]))\d{6}|8\d{6,9}|9\d{6,10}|1\d{4,5}')
            ->setPossibleLength([5, 6, 7, 8, 9, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:6[1-58]|970\d)\d{7}')
            ->setExampleNumber('612345678')
            ->setPossibleLength([9, 11]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90[069]\d{4,7}')
            ->setExampleNumber('9061234')
            ->setPossibleLength([7, 8, 9, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:[035]\d|1[13-578]|6[124-8]|7[24]|8[0-467])|2(?:[0346]\d|2[2-46-9]|5[125]|9[479])|3(?:[03568]\d|1[3-8]|2[01]|4[1-8])|4(?:[0356]\d|1[1-368]|7[58]|8[15-8]|9[23579])|5(?:[0358]\d|[19][1-9]|2[1-57-9]|4[13-8]|6[126]|7[0-3578])|7\d\d)\d{6}')
            ->setExampleNumber('101234567')
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['1[238]|[34]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['14'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{6})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4,7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[89]0'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['66'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{8})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['6'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1[16-8]|2[259]|3[124]|4[17-9]|5[124679]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[1-578]|91'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{4,7}')
            ->setExampleNumber('8001234')
            ->setPossibleLength([7, 8, 9, 10]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:85|91)\d{7}')
            ->setExampleNumber('851234567')
            ->setPossibleLength([9]);
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('66\d{7}')
            ->setExampleNumber('662345678')
            ->setPossibleLength([9]);
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('140(?:1[035]|2[0346]|3[03568]|4[0356]|5[0358]|8[458])|(?:140(?:1[16-8]|2[259]|3[124]|4[17-9]|5[124679]|7)|8[478]\d{6})\d')
            ->setExampleNumber('14020')
            ->setPossibleLength([5, 6, 9]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('140(?:1[035]|2[0346]|3[03568]|4[0356]|5[0358]|8[458])|140(?:1[16-8]|2[259]|3[124]|4[17-9]|5[124679]|7)\d')
            ->setPossibleLength([5, 6]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4,7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[89]0'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['66'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{8})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['6'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1[16-8]|2[259]|3[124]|4[17-9]|5[124679]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[1-578]|91'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
