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
class PhoneNumberMetadata_AT extends PhoneMetadata
{
    protected const ID = 'AT';
    protected const COUNTRY_CODE = 43;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d{3,12}|2\d{6,12}|43(?:(?:0\d|5[02-9])\d{3,9}|2\d{4,5}|[3467]\d{4}|8\d{4,6}|9\d{4,7})|5\d{4,12}|8\d{7,12}|9\d{8,12}|(?:[367]\d|4[0-24-9])\d{4,11}')
            ->setPossibleLengthLocalOnly([3])
            ->setPossibleLength([4, 5, 6, 7, 8, 9, 10, 11, 12, 13]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6(?:485|(?:5[0-3579]|6[013-9]|[7-9]\d)\d)\d{3,9}')
            ->setExampleNumber('664123456')
            ->setPossibleLength([7, 8, 9, 10, 11, 12, 13]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:8[69][2-68]|9(?:0[01]|3[019]))\d{6,10}')
            ->setExampleNumber('900123456')
            ->setPossibleLength([9, 10, 11, 12, 13]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:11\d|[2-9]\d{3,11})|(?:316|463)\d{3,10}|648[34]\d{3,9}|(?:51|66|73)2\d{3,10}|(?:2(?:1[467]|2[13-8]|5[2357]|6[1-46-8]|7[1-8]|8[124-7]|9[1458])|3(?:1[1-578]|3[23568]|4[5-7]|5[1378]|6[1-38]|8[3-68])|4(?:2[1-8]|35|7[1368]|8[2457])|5(?:2[1-8]|3[357]|4[147]|5[12578]|6[37])|6(?:13|2[1-47]|4[135-7]|5[468])|7(?:2[1-8]|35|4[13478]|5[68]|6[16-8]|7[1-6]|9[45]))\d{4,10}')
            ->setExampleNumber('1234567890')
            ->setPossibleLengthLocalOnly([3]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['14'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3,12})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['1(?:11|[2-9])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['517'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['5[079]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{6})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['[18]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,10})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:31|4)6|51|6(?:48|5[0-3579]|[6-9])|7(?:20|32|8)|[89]', '(?:31|4)6|51|6(?:485|5[0-3579]|[6-9])|7(?:20|32|8)|[89]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3,9})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[2-467]|5[2-6]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['5'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4,7})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['5'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6,10}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([9, 10, 11, 12, 13]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:10|2[018])\d{6,10}|828\d{5}')
            ->setExampleNumber('810123456')
            ->setPossibleLength([8, 9, 10, 11, 12, 13]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5(?:0[1-9]|17|[79]\d)\d{2,10}|7[28]0\d{6,10}')
            ->setExampleNumber('780123456')
            ->setPossibleLength([5, 6, 7, 8, 9, 10, 11, 12, 13]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{3,12})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['1(?:11|[2-9])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['517'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['5[079]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,10})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:31|4)6|51|6(?:48|5[0-3579]|[6-9])|7(?:20|32|8)|[89]', '(?:31|4)6|51|6(?:485|5[0-3579]|[6-9])|7(?:20|32|8)|[89]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3,9})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[2-467]|5[2-6]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['5'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4,7})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['5'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
