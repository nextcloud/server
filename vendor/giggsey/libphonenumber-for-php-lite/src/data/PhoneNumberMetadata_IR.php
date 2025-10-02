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
class PhoneNumberMetadata_IR extends PhoneMetadata
{
    protected const ID = 'IR';
    protected const COUNTRY_CODE = 98;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-9]\d{9}|(?:[1-8]\d\d|9)\d{3,4}')
            ->setPossibleLengthLocalOnly([8])
            ->setPossibleLength([4, 5, 6, 7, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:(?:0[0-5]|[13]\d|2[0-3])\d\d|9(?:[0-46]\d\d|5(?:10|5\d)|8(?:[12]\d|88)|9(?:0[0-3]|[19]\d|21|69|77|8[7-9])))\d{5}')
            ->setExampleNumber('9123456789')
            ->setPossibleLength([10]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1[137]|2[13-68]|3[1458]|4[145]|5[1468]|6[16]|7[1467]|8[13467])(?:[03-57]\d{7}|[16]\d{3}(?:\d{4})?|[289]\d{3}(?:\d(?:\d{3})?)?)|94(?:000[09]|(?:12\d|30[0-2])\d|2(?:121|[2689]0\d)|4(?:111|40\d))\d{4}')
            ->setExampleNumber('2123456789')
            ->setPossibleLengthLocalOnly([4, 5, 8])
            ->setPossibleLength([6, 7, 10]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4,5})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['96'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:1[137]|2[13-68]|3[1458]|4[145]|5[1468]|6[16]|7[1467]|8[13467])[12689]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[1-8]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('96(?:0[12]|2[16-8]|3(?:08|[14]5|[23]|66)|4(?:0|80)|5[01]|6[89]|86|9[19])')
            ->setExampleNumber('9601')
            ->setPossibleLength([4, 5]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:4440\d{5}|6(?:0[12]|2[16-8]|3(?:08|[14]5|[23]|66)|4(?:0|80)|5[01]|6[89]|86|9[19]))')
            ->setPossibleLength([4, 5, 10]);
    }
}
