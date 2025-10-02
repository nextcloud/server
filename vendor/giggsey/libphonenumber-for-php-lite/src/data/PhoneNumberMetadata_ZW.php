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
class PhoneNumberMetadata_ZW extends PhoneMetadata
{
    protected const ID = 'ZW';
    protected const COUNTRY_CODE = 263;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:[0-57-9]\d{6,8}|6[0-24-9]\d{6,7})|[38]\d{9}|[35-8]\d{8}|[3-6]\d{7}|[1-689]\d{6}|[1-3569]\d{5}|[1356]\d{4}')
            ->setPossibleLengthLocalOnly([3, 4])
            ->setPossibleLength([5, 6, 7, 8, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7(?:[1278]\d|3[1-9])\d{6}')
            ->setExampleNumber('712345678')
            ->setPossibleLength([9]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:(?:3\d|9)\d|[4-8])|2(?:(?:(?:0(?:2[014]|5)|(?:2[0157]|31|84|9)\d\d|[56](?:[14]\d\d|20)|7(?:[089]|2[03]|[35]\d\d))\d|4(?:2\d\d|8))\d|1(?:2|[39]\d{4}))|3(?:(?:123|(?:29\d|92)\d)\d\d|7(?:[19]|[56]\d))|5(?:0|1[2-478]|26|[37]2|4(?:2\d{3}|83)|5(?:25\d\d|[78])|[689]\d)|6(?:(?:[16-8]21|28|52[013])\d\d|[39])|8(?:[1349]28|523)\d\d)\d{3}|(?:4\d\d|9[2-9])\d{4,5}|(?:(?:2(?:(?:(?:0|8[146])\d|7[1-7])\d|2(?:[278]\d|92)|58(?:2\d|3))|3(?:[26]|9\d{3})|5(?:4\d|5)\d\d)\d|6(?:(?:(?:[0-246]|[78]\d)\d|37)\d|5[2-8]))\d\d|(?:2(?:[569]\d|8[2-57-9])|3(?:[013-59]\d|8[37])|6[89]8)\d{3}')
            ->setExampleNumber('1312345')
            ->setPossibleLengthLocalOnly([3, 4]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['2(?:0[45]|2[278]|[49]8)|3(?:[09]8|17)|6(?:[29]8|37|75)|[23][78]|(?:33|5[15]|6[68])[78]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{2,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[49]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['80'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern([
                    '24|8[13-59]|(?:2[05-79]|39|5[45]|6[15-8])2',
                    '2(?:02[014]|4|[56]20|[79]2)|392|5(?:42|525)|6(?:[16-8]21|52[013])|8[13-59]',
                ])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2(?:1[39]|2[0157]|[378]|[56][14])|3(?:12|29)', '2(?:1[39]|2[0157]|[378]|[56][14])|3(?:123|29)'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern([
                    '1|2(?:0[0-36-9]|12|29|[56])|3(?:1[0-689]|[24-6])|5(?:[0236-9]|1[2-4])|6(?:[013-59]|7[0-46-9])|(?:33|55|6[68])[0-69]|(?:29|3[09]|62)[0-79]',
                ])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['29[013-9]|39|54'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:25|54)8', '258|5483'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80(?:[01]\d|20|8[0-8])\d{3}')
            ->setExampleNumber('8001234')
            ->setPossibleLength([7]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('86(?:1[12]|22|30|44|55|77|8[368])\d{6}')
            ->setExampleNumber('8686123456')
            ->setPossibleLength([10]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
