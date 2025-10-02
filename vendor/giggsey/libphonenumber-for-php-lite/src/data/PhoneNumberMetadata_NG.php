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
class PhoneNumberMetadata_NG extends PhoneMetadata
{
    protected const ID = 'NG';
    protected const COUNTRY_CODE = 234;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '009';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:20|9\d)\d{8}|[78]\d{9,13}')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([10, 11, 12, 13, 14]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:702[0-24-9]|819[01])\d{6}|(?:7(?:0[13-9]|[12]\d)|8(?:0[1-9]|1[0-8])|9(?:0[1-9]|1[1-6]))\d{7}')
            ->setExampleNumber('8021234567')
            ->setPossibleLength([10]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('20(?:[1259]\d|3[013-9]|4[1-8]|6[024-689]|7[1-79]|8[2-9])\d{6}')
            ->setExampleNumber('2033123456')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([10]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[7-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['20[129]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{2})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})(\d{4,5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[78]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})(\d{5,6})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[78]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{7,11}')
            ->setExampleNumber('80017591759');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('700\d{7,11}')
            ->setExampleNumber('7001234567');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
