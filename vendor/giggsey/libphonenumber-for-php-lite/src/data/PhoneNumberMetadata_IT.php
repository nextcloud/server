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
class PhoneNumberMetadata_IT extends PhoneMetadata
{
    protected const ID = 'IT';
    protected const COUNTRY_CODE = 39;

    protected ?string $internationalPrefix = '00';
    protected bool $mainCountryForCode = true;
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0\d{5,10}|1\d{8,10}|3(?:[0-8]\d{7,10}|9\d{7,8})|(?:43|55|70)\d{8}|8\d{5}(?:\d{2,4})?')
            ->setPossibleLength([6, 7, 8, 9, 10, 11, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3[2-9]\d{7,8}|(?:31|43)\d{8}')
            ->setExampleNumber('3123456789')
            ->setPossibleLength([9, 10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:0878\d{3}|89(?:2\d|3[04]|4(?:[0-4]|[5-9]\d\d)|5[0-4]))\d\d|(?:1(?:44|6[346])|89(?:38|5[5-9]|9))\d{6}')
            ->setExampleNumber('899123456')
            ->setPossibleLength([6, 8, 9, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0669[0-79]\d{1,6}|0(?:1(?:[0159]\d|[27][1-5]|31|4[1-4]|6[1356]|8[2-57])|2\d\d|3(?:[0159]\d|2[1-4]|3[12]|[48][1-6]|6[2-59]|7[1-7])|4(?:[0159]\d|[23][1-9]|4[245]|6[1-5]|7[1-4]|81)|5(?:[0159]\d|2[1-5]|3[2-6]|4[1-79]|6[4-6]|7[1-578]|8[3-8])|6(?:[0-57-9]\d|6[0-8])|7(?:[0159]\d|2[12]|3[1-7]|4[2-46]|6[13569]|7[13-6]|8[1-59])|8(?:[0159]\d|2[3-578]|3[1-356]|[6-8][1-5])|9(?:[0159]\d|[238][1-5]|4[12]|6[1-8]|7[1-6]))\d{2,7}')
            ->setExampleNumber('0212345678')
            ->setPossibleLength([6, 7, 8, 9, 10, 11]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4,5})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['1(?:0|9[246])', '1(?:0|9(?:2[2-9]|[46]))'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{6})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['1(?:1|92)'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['0[26]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['0[13-57-9][0159]|8(?:03|4[17]|9[2-5])', '0[13-57-9][0159]|8(?:03|4[17]|9(?:2|3[04]|[45][0-4]))'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{2,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['0(?:[13-579][2-46-8]|8[236-8])'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['894'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0[26]|5'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1(?:44|[679])|[378]|43'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0[13-57-9][0159]|14'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0[26]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})(\d{4,5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['3'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80(?:0\d{3}|3)\d{3}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([6, 9]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('84(?:[08]\d{3}|[17])\d{3}')
            ->setExampleNumber('848123456')
            ->setPossibleLength([6, 9]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:78\d|99)\d{6}')
            ->setExampleNumber('1781234567')
            ->setPossibleLength([9, 10]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('55\d{8}')
            ->setExampleNumber('5512345678')
            ->setPossibleLength([10]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3[2-8]\d{9,10}')
            ->setExampleNumber('33101234501')
            ->setPossibleLength([11, 12]);
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('848\d{6}')
            ->setPossibleLength([9]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['0[26]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['0[13-57-9][0159]|8(?:03|4[17]|9[2-5])', '0[13-57-9][0159]|8(?:03|4[17]|9(?:2|3[04]|[45][0-4]))'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{2,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['0(?:[13-579][2-46-8]|8[236-8])'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['894'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0[26]|5'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1(?:44|[679])|[378]|43'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0[13-57-9][0159]|14'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0[26]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})(\d{4,5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['3'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
