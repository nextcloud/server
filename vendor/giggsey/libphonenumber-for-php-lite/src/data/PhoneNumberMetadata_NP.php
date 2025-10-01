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
class PhoneNumberMetadata_NP extends PhoneMetadata
{
    protected const ID = 'NP';
    protected const COUNTRY_CODE = 977;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1\d|9)\d{9}|[1-9]\d{7}')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([8, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:00|6[0-3]|7[024-6]|8[0-24-68])\d{7}')
            ->setExampleNumber('9841234567')
            ->setPossibleLength([10]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1[0-6]\d|99[02-6])\d{5}|(?:2[13-79]|3[135-8]|4[146-9]|5[135-7]|6[13-9]|7[15-9]|8[1-46-9]|9[1-7])[2-6]\d{5}')
            ->setExampleNumber('14567890')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{7})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['1[2-6]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['1[01]|[2-8]|9(?:[1-59]|[67][2-6])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{7})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{2})(\d{5})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:66001|800\d\d)\d{5}')
            ->setExampleNumber('16600101234')
            ->setPossibleLength([11]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{7})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['1[2-6]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['1[01]|[2-8]|9(?:[1-59]|[67][2-6])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{7})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
