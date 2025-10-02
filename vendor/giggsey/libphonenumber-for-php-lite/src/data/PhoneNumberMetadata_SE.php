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
class PhoneNumberMetadata_SE extends PhoneMetadata
{
    protected const ID = 'SE';
    protected const COUNTRY_CODE = 46;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[26]\d\d|9)\d{9}|[1-9]\d{8}|[1-689]\d{7}|[1-4689]\d{6}|2\d{5}')
            ->setPossibleLength([6, 7, 8, 9, 10, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7[02369]\d{7}')
            ->setExampleNumber('701234567')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('649\d{6}|99[1-59]\d{4}(?:\d{3})?|9(?:00|39|44)[1-8]\d{3,6}')
            ->setExampleNumber('9001234567')
            ->setPossibleLength([7, 8, 9, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:(?:[12][136]|3[356]|4[0246]|6[03]|8\d)\d|90[1-9])\d{4,6}|(?:1(?:2[0-35]|4[0-4]|5[0-25-9]|7[13-6]|[89]\d)|2(?:2[0-7]|4[0136-8]|5[0138]|7[018]|8[01]|9[0-57])|3(?:0[0-4]|1\d|2[0-25]|4[056]|7[0-2]|8[0-3]|9[023])|4(?:1[013-8]|3[0135]|5[14-79]|7[0-246-9]|8[0156]|9[0-689])|5(?:0[0-6]|[15][0-5]|2[0-68]|3[0-4]|4\d|6[03-5]|7[013]|8[0-79]|9[01])|6(?:1[1-3]|2[0-4]|4[02-57]|5[0-37]|6[0-3]|7[0-2]|8[0247]|9[0-356])|9(?:1[0-68]|2\d|3[02-5]|4[0-3]|5[0-4]|[68][01]|7[0135-8]))\d{5,6}')
            ->setExampleNumber('8123456')
            ->setPossibleLength([7, 8, 9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2,3})(\d{2})')
                ->setFormat('$1-$2 $3')
                ->setLeadingDigitsPattern(['20'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['9(?:00|39|44|9)'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})')
                ->setFormat('$1-$2 $3')
                ->setLeadingDigitsPattern(['[12][136]|3[356]|4[0246]|6[03]|90[1-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{2,3})(\d{2})(\d{2})')
                ->setFormat('$1-$2 $3 $4')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2,3})(\d{2})')
                ->setFormat('$1-$2 $3')
                ->setLeadingDigitsPattern(['1[2457]|2(?:[247-9]|5[0138])|3[0247-9]|4[1357-9]|5[0-35-9]|6(?:[125689]|4[02-57]|7[0-2])|9(?:[125-8]|3[02-5]|4[0-3])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2,3})(\d{3})')
                ->setFormat('$1-$2 $3')
                ->setLeadingDigitsPattern(['9(?:00|39|44)'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2,3})(\d{2})(\d{2})')
                ->setFormat('$1-$2 $3 $4')
                ->setLeadingDigitsPattern(['1[13689]|2[0136]|3[1356]|4[0246]|54|6[03]|90[1-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1-$2 $3 $4')
                ->setLeadingDigitsPattern(['10|7'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{3})(\d{2})')
                ->setFormat('$1-$2 $3 $4')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1-$2 $3 $4')
                ->setLeadingDigitsPattern(['[13-5]|2(?:[247-9]|5[0138])|6(?:[124-689]|7[0-2])|9(?:[125-8]|3[02-5]|4[0-3])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{3})')
                ->setFormat('$1-$2 $3 $4')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1-$2 $3 $4 $5')
                ->setLeadingDigitsPattern(['[26]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('20\d{4,7}')
            ->setExampleNumber('20123456')
            ->setPossibleLength([6, 7, 8, 9]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('77[0-7]\d{6}')
            ->setExampleNumber('771234567')
            ->setPossibleLength([9]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('75[1-8]\d{6}')
            ->setExampleNumber('751234567')
            ->setPossibleLength([9]);
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('74[02-9]\d{6}')
            ->setExampleNumber('740123456')
            ->setPossibleLength([9]);
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('10[1-8]\d{6}')
            ->setExampleNumber('102345678')
            ->setPossibleLength([9]);
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:25[245]|67[3-68])\d{9}')
            ->setExampleNumber('254123456789')
            ->setPossibleLength([12]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2,3})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['20']),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['9(?:00|39|44|9)']),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[12][136]|3[356]|4[0246]|6[03]|90[1-9]']),
            (new NumberFormat())
                ->setPattern('(\d)(\d{2,3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['8']),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2,3})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1[2457]|2(?:[247-9]|5[0138])|3[0247-9]|4[1357-9]|5[0-35-9]|6(?:[125689]|4[02-57]|7[0-2])|9(?:[125-8]|3[02-5]|4[0-3])']),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2,3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9(?:00|39|44)']),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2,3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['1[13689]|2[0136]|3[1356]|4[0246]|54|6[03]|90[1-9]']),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['10|7']),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{3})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['8']),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[13-5]|2(?:[247-9]|5[0138])|6(?:[124-689]|7[0-2])|9(?:[125-8]|3[02-5]|4[0-3])']),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['9']),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4 $5')
                ->setLeadingDigitsPattern(['[26]']),
        ];
    }
}
