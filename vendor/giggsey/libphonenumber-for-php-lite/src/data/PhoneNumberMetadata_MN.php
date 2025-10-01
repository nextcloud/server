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
class PhoneNumberMetadata_MN extends PhoneMetadata
{
    protected const ID = 'MN';
    protected const COUNTRY_CODE = 976;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '001';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[12]\d{7,9}|[5-9]\d{7}')
            ->setPossibleLengthLocalOnly([4, 5, 6])
            ->setPossibleLength([8, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:83[01]|92[0139])\d{5}|(?:5[05]|6[069]|72|8[015689]|9[013-9])\d{6}')
            ->setExampleNumber('88123456')
            ->setPossibleLength([8]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[12]2[1-3]\d{5,6}|(?:(?:[12](?:1|27)|5[368])\d\d|7(?:0(?:[0-5]\d|7[078]|80)|128))\d{4}|[12](?:3[2-8]|4[2-68]|5[1-4689])\d{6,7}')
            ->setExampleNumber('53123456')
            ->setPossibleLengthLocalOnly([4, 5, 6]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[12]1'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[5-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[12]2[1-3]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{5,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[12](?:27|3[2-8]|4[2-68]|5[1-4689])', '[12](?:27|3[2-8]|4[2-68]|5[1-4689])[0-3]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{4,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[12]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('712[0-79]\d{4}|7(?:1[013-9]|[5-9]\d)\d{5}')
            ->setExampleNumber('75123456')
            ->setPossibleLength([8]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
