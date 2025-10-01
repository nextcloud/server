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
class PhoneNumberMetadata_RU extends PhoneMetadata
{
    protected const ID = 'RU';
    protected const COUNTRY_CODE = 7;
    protected const NATIONAL_PREFIX = '8';

    protected ?string $nationalPrefixForParsing = '8';
    protected ?string $internationalPrefix = '810';
    protected ?string $preferredInternationalPrefix = '8~10';
    protected bool $mainCountryForCode = true;
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8\d{13}|[347-9]\d{9}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10, 14]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9\d{9}')
            ->setExampleNumber('9123456789')
            ->setPossibleLength([10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[39]\d{7}')
            ->setExampleNumber('8091234567')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('336(?:[013-9]\d|2[013-9])\d{5}|(?:3(?:0[12]|4[1-35-79]|5[1-3]|65|8[1-58]|9[0145])|4(?:01|1[1356]|2[13467]|7[1-5]|8[1-7]|9[1-689])|8(?:1[1-8]|2[01]|3[13-6]|4[0-8]|5[15-7]|6[0-35-79]|7[1-37-9]))\d{7}')
            ->setExampleNumber('3011234567')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['[0-79]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern([
                    '7(?:1[0-8]|2[1-9])',
                    '7(?:1(?:[0-356]2|4[29]|7|8[27])|2(?:1[23]|[2-9]2))',
                    '7(?:1(?:[0-356]2|4[29]|7|8[27])|2(?:13[03-69]|62[013-9]))|72[1-57-9]2',
                ])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d)(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern([
                    '7(?:1[0-68]|2[1-9])',
                    '7(?:1(?:[06][3-6]|[18]|2[35]|[3-5][3-5])|2(?:[13][3-5]|[24-689]|7[457]))',
                    '7(?:1(?:0(?:[356]|4[023])|[18]|2(?:3[013-9]|5)|3[45]|43[013-79]|5(?:3[1-8]|4[1-7]|5)|6(?:3[0-35-9]|[4-6]))|2(?:1(?:3[178]|[45])|[24-689]|3[35]|7[457]))|7(?:14|23)4[0-8]|71(?:33|45)[1-79]',
                ])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2-$3-$4')
                ->setLeadingDigitsPattern(['[349]|8(?:[02-7]|1[1-8])'])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:0[04]|108\d{3})\d{7}')
            ->setExampleNumber('8001234567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('808\d{7}')
            ->setExampleNumber('8081234567')
            ->setPossibleLength([10]);
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern([
                    '7(?:1[0-8]|2[1-9])',
                    '7(?:1(?:[0-356]2|4[29]|7|8[27])|2(?:1[23]|[2-9]2))',
                    '7(?:1(?:[0-356]2|4[29]|7|8[27])|2(?:13[03-69]|62[013-9]))|72[1-57-9]2',
                ])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d)(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern([
                    '7(?:1[0-68]|2[1-9])',
                    '7(?:1(?:[06][3-6]|[18]|2[35]|[3-5][3-5])|2(?:[13][3-5]|[24-689]|7[457]))',
                    '7(?:1(?:0(?:[356]|4[023])|[18]|2(?:3[013-9]|5)|3[45]|43[013-79]|5(?:3[1-8]|4[1-7]|5)|6(?:3[0-35-9]|[4-6]))|2(?:1(?:3[178]|[45])|[24-689]|3[35]|7[457]))|7(?:14|23)4[0-8]|71(?:33|45)[1-79]',
                ])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2-$3-$4')
                ->setLeadingDigitsPattern(['[349]|8(?:[02-7]|1[1-8])'])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('8 ($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
