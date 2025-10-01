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
class PhoneNumberMetadata_IL extends PhoneMetadata
{
    protected const ID = 'IL';
    protected const COUNTRY_CODE = 972;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '0(?:0|1[2-9])';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d{6}(?:\d{3,5})?|[57]\d{8}|[1-489]\d{7}')
            ->setPossibleLength([7, 8, 9, 10, 11, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('55(?:4(?:[01]0|5[0-2])|57[0-289])\d{4}|5(?:(?:[0-2][02-9]|[36]\d|[49][2-9]|8[3-7])\d|5(?:01|2\d|3[0-3]|4[34]|5[0-25689]|6[6-8]|7[0-267]|8[7-9]|9[1-9]))\d{5}')
            ->setExampleNumber('502345678')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1212\d{4}|1(?:200|9(?:0[0-2]|19))\d{6}')
            ->setExampleNumber('1919123456')
            ->setPossibleLength([8, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('153\d{8,9}|29[1-9]\d{5}|(?:2[0-8]|[3489]\d)\d{6}')
            ->setExampleNumber('21234567')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([8, 11, 12]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['125'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{2})(\d{2})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['121'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['[2-489]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['[57]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{3})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['12'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{6})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['159'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1-$2-$3-$4')
                ->setLeadingDigitsPattern(['1[7-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{1,2})(\d{3})(\d{4})')
                ->setFormat('$1-$2 $3-$4')
                ->setLeadingDigitsPattern(['15'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:255|80[019]\d{3})\d{3}')
            ->setExampleNumber('1800123456')
            ->setPossibleLength([7, 10]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1700\d{6}')
            ->setExampleNumber('1700123456')
            ->setPossibleLength([10]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7(?:38(?:[05]\d|8[08])|8(?:33|55|77|81)\d)\d{4}|7(?:18|2[23]|3[237]|47|6[258]|7\d|82|9[2-9])\d{6}')
            ->setExampleNumber('771234567')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1599\d{6}')
            ->setExampleNumber('1599123456')
            ->setPossibleLength([10]);
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('151\d{8,9}')
            ->setExampleNumber('15112340000')
            ->setPossibleLength([11, 12]);
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1700\d{6}')
            ->setPossibleLength([10]);
    }
}
