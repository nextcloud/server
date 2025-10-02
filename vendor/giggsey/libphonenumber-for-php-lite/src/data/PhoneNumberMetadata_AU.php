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
class PhoneNumberMetadata_AU extends PhoneMetadata
{
    protected const ID = 'AU';
    protected const COUNTRY_CODE = 61;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '(183[12])|0';
    protected ?string $internationalPrefix = '001[14-689]|14(?:1[14]|34|4[17]|[56]6|7[47]|88)0011';
    protected ?string $preferredInternationalPrefix = '0011';
    protected bool $mainCountryForCode = true;
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:[0-79]\d{7}(?:\d(?:\d{2})?)?|8[0-24-9]\d{7})|[2-478]\d{8}|1\d{4,7}')
            ->setPossibleLength([5, 6, 7, 8, 9, 10, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4(?:79[01]|83[0-389]|94[0-478])\d{5}|4(?:[0-36]\d|4[047-9]|5[0-25-9]|7[02-8]|8[0-24-9]|9[0-37-9])\d{6}')
            ->setExampleNumber('412345678')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('190[0-26]\d{6}')
            ->setExampleNumber('1900123456')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:(?:2(?:(?:[0-26-9]\d|3[0-8]|5[0135-9])\d|4(?:[02-9]\d|10))|3(?:(?:[0-3589]\d|6[1-9]|7[0-35-9])\d|4(?:[0-578]\d|90))|7(?:[013-57-9]\d|2[0-8])\d)\d\d|8(?:51(?:0(?:0[03-9]|[12479]\d|3[2-9]|5[0-8]|6[1-9]|8[0-7])|1(?:[0235689]\d|1[0-69]|4[0-589]|7[0-47-9])|2(?:0[0-79]|[18][13579]|2[14-9]|3[0-46-9]|[4-6]\d|7[89]|9[0-4])|[34]\d\d)|(?:6[0-8]|[78]\d)\d{3}|9(?:[02-9]\d{3}|1(?:(?:[0-58]\d|6[0135-9])\d|7(?:0[0-24-9]|[1-9]\d)|9(?:[0-46-9]\d|5[0-79])))))\d{3}')
            ->setExampleNumber('212345678')
            ->setPossibleLengthLocalOnly([8])
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['16'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['13'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['19'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['180', '1802'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3,4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['19'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['16'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['14|4'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2378]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setDomesticCarrierCodeFormattingRule('$CC ($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1(?:30|[89])'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['130'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('180(?:0\d{3}|2)\d{3}')
            ->setExampleNumber('1800123456')
            ->setPossibleLength([7, 10]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('13(?:00\d{6}(?:\d{2})?|45[0-4]\d{3})|13\d{4}')
            ->setExampleNumber('1300123456')
            ->setPossibleLength([6, 8, 10, 12]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('14(?:5(?:1[0458]|[23][458])|71\d)\d{4}')
            ->setExampleNumber('147101234')
            ->setPossibleLength([9]);
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('163\d{2,6}')
            ->setExampleNumber('1631234')
            ->setPossibleLength([5, 6, 7, 8, 9]);
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:3(?:00\d{5}|45[0-4])|802)\d{3}|1[38]00\d{6}|13\d{4}')
            ->setPossibleLength([6, 7, 8, 10, 12]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['16'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['16'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['14|4'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2378]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setDomesticCarrierCodeFormattingRule('$CC ($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1(?:30|[89])'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
