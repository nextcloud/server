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
class PhoneNumberMetadata_BR extends PhoneMetadata
{
    protected const ID = 'BR';
    protected const COUNTRY_CODE = 55;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '(?:0|90)(?:(1[245]|2[1-35]|31|4[13]|[56]5|99)(\d{10,11}))?';
    protected ?string $internationalPrefix = '00(?:1[245]|2[1-35]|31|4[13]|[56]5|99)';
    protected ?string $nationalPrefixTransformRule = '$2';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-467]\d{9,10}|55[0-46-9]\d{8}|[34]\d{7}|55\d{7,8}|(?:5[0-46-9]|[89]\d)\d{7,9}')
            ->setPossibleLength([8, 9, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[14689][1-9]|2[12478]|3[1-578]|5[13-5]|7[13-579])(?:7|9\d)\d{7}')
            ->setExampleNumber('11961234567')
            ->setPossibleLengthLocalOnly([8, 9])
            ->setPossibleLength([10, 11]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[59]00\d{6,7}')
            ->setExampleNumber('500123456')
            ->setPossibleLength([9, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[14689][1-9]|2[12478]|3[1-578]|5[13-5]|7[13-579])[2-5]\d{7}')
            ->setExampleNumber('1123456789')
            ->setPossibleLengthLocalOnly([8])
            ->setPossibleLength([10]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3,6})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['1(?:1[25-8]|2[357-9]|3[02-68]|4[12568]|5|6[0-8]|8[015]|9[0-47-9])|321|610'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['300|4(?:0[02]|37|86)', '300|4(?:0(?:0|20)|370|864)'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[2-57]', '[2357]|4(?:[0-24-9]|3(?:[0-689]|7[1-9]))'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2,3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['(?:[358]|90)0'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1 $2-$3')
                ->setLeadingDigitsPattern(['(?:[14689][1-9]|2[12478]|3[1-578]|5[13-5]|7[13-579])[2-57]'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setDomesticCarrierCodeFormattingRule('0 $CC ($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5})(\d{4})')
                ->setFormat('$1 $2-$3')
                ->setLeadingDigitsPattern(['[16][1-9]|[2-57-9]'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setDomesticCarrierCodeFormattingRule('0 $CC ($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6,7}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([9, 10]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:30[03]\d{3}|4(?:0(?:0\d|20)|370|864))\d{4}|300\d{5}')
            ->setExampleNumber('40041234')
            ->setPossibleLength([8, 10]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:30[03]\d{3}|4(?:0(?:0\d|20)|864))\d{4}|800\d{6,7}|300\d{5}')
            ->setPossibleLength([8, 9, 10]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['300|4(?:0[02]|37|86)', '300|4(?:0(?:0|20)|370|864)'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2,3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['(?:[358]|90)0'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1 $2-$3')
                ->setLeadingDigitsPattern(['(?:[14689][1-9]|2[12478]|3[1-578]|5[13-5]|7[13-579])[2-57]'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setDomesticCarrierCodeFormattingRule('0 $CC ($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5})(\d{4})')
                ->setFormat('$1 $2-$3')
                ->setLeadingDigitsPattern(['[16][1-9]|[2-57-9]'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setDomesticCarrierCodeFormattingRule('0 $CC ($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
