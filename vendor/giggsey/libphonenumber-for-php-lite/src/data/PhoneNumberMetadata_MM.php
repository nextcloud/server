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
class PhoneNumberMetadata_MM extends PhoneMetadata
{
    protected const ID = 'MM';
    protected const COUNTRY_CODE = 95;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d{5,7}|95\d{6}|(?:[4-7]|9[0-46-9])\d{6,8}|(?:2|8\d)\d{5,8}')
            ->setPossibleLengthLocalOnly([5])
            ->setPossibleLength([6, 7, 8, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:17[01]|9(?:2(?:[0-4]|[56]\d\d)|(?:3(?:[0-36]|4\d)|(?:6\d|8[89]|9[4-8])\d|7(?:3|40|[5-9]\d))\d|4(?:(?:[0245]\d|[1379])\d|88)|5[0-6])\d)\d{4}|9[69]1\d{6}|9(?:[68]\d|9[089])\d{5}')
            ->setExampleNumber('92123456')
            ->setPossibleLength([7, 8, 9, 10]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:(?:12|[28]\d|3[56]|7[3-6]|9[0-6])\d|4(?:2[29]|7[0-2]|83)|6)|2(?:2(?:00|8[34])|4(?:0\d|22|7[0-2]|83)|51\d\d)|4(?:2(?:2\d\d|48[013])|3(?:20\d|4(?:70|83)|56)|420\d|5(?:2\d|470))|6(?:0(?:[23]|88\d)|(?:124|[56]2\d)\d|2472|3(?:20\d|470)|4(?:2[04]\d|472)|7(?:3\d\d|4[67]0|8(?:[01459]\d|8))))\d{4}|5(?:2(?:2\d{5,6}|47[02]\d{4})|(?:3472|4(?:2(?:1|86)|470)|522\d|6(?:20\d|483)|7(?:20\d|48[01])|8(?:20\d|47[02])|9(?:20\d|470))\d{4})|7(?:(?:0470|4(?:25\d|470)|5(?:202|470|96\d))\d{4}|1(?:20\d{4,5}|4(?:70|83)\d{4}))|8(?:1(?:2\d{5,6}|4(?:10|7[01]\d)\d{3})|2(?:2\d{5,6}|(?:320|490\d)\d{3})|(?:3(?:2\d\d|470)|4[24-7]|5(?:(?:2\d|51)\d|4(?:[1-35-9]\d|4[0-57-9]))|6[23])\d{4})|(?:1[2-6]\d|4(?:2[24-8]|3[2-7]|[46][2-6]|5[3-5])|5(?:[27][2-8]|3[2-68]|4[24-8]|5[23]|6[2-4]|8[24-7]|9[2-7])|6(?:[19]20|42[03-6]|(?:52|7[45])\d)|7(?:[04][24-8]|[15][2-7]|22|3[2-4])|8(?:1[2-689]|2[2-8]|(?:[35]2|64)\d))\d{4}|25\d{5,6}|(?:2[2-9]|6(?:1[2356]|[24][2-6]|3[24-6]|5[2-4]|6[2-8]|7[235-7]|8[245]|9[24])|8(?:3[24]|5[245]))\d{4}')
            ->setExampleNumber('1234567')
            ->setPossibleLengthLocalOnly([5])
            ->setPossibleLength([6, 7, 8, 9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['16|2'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['4(?:[2-46]|5[3-5])|5|6(?:[1-689]|7[235-7])|7(?:[0-4]|5[2-7])|8[1-5]|(?:60|86)[23]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[12]|452|678|86', '[12]|452|6788|86'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[4-7]|8[1-35]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{4,6})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9(?:2[0-4]|[35-9]|4[137-9])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['92'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{5})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80080(?:0[1-9]|2\d)\d{3}')
            ->setExampleNumber('8008001234')
            ->setPossibleLength([10]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1333\d{4}')
            ->setExampleNumber('13331234')
            ->setPossibleLength([8]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
