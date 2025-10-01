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
class PhoneNumberMetadata_IE extends PhoneMetadata
{
    protected const ID = 'IE';
    protected const COUNTRY_CODE = 353;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1\d|[2569])\d{6,8}|4\d{6,9}|7\d{8}|8\d{8,9}')
            ->setPossibleLengthLocalOnly([5, 6])
            ->setPossibleLength([7, 8, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:22|[35-9]\d)\d{6}')
            ->setExampleNumber('850123456')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('15(?:1[2-8]|[2-8]0|9[089])\d{6}')
            ->setExampleNumber('1520123456')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1\d|21)\d{6,7}|(?:2[24-9]|4(?:0[24]|5\d|7)|5(?:0[45]|1\d|8)|6(?:1\d|[237-9])|9(?:1\d|[35-9]))\d{5}|(?:23|4(?:[1-469]|8\d)|5[23679]|6[4-6]|7[14]|9[04])\d{7}')
            ->setExampleNumber('2212345')
            ->setPossibleLengthLocalOnly([5, 6]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['2[24-9]|47|58|6[237-9]|9[35-9]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[45]0'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2569]|4[1-69]|7[14]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['70'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['81'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[78]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['4'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d)(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1800\d{6}')
            ->setExampleNumber('1800123456')
            ->setPossibleLength([10]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('18[59]0\d{6}')
            ->setExampleNumber('1850123456')
            ->setPossibleLength([10]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('700\d{6}')
            ->setExampleNumber('700123456')
            ->setPossibleLength([9]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('76\d{7}')
            ->setExampleNumber('761234567')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('818\d{6}')
            ->setExampleNumber('818123456')
            ->setPossibleLength([9]);
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('88210[1-9]\d{4}|8(?:[35-79]5\d\d|8(?:[013-9]\d\d|2(?:[01][1-9]|[2-9]\d)))\d{5}')
            ->setExampleNumber('8551234567')
            ->setPossibleLength([10]);
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('18[59]0\d{6}')
            ->setPossibleLength([10]);
    }
}
