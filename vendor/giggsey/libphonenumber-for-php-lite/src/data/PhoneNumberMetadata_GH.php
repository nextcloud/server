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
class PhoneNumberMetadata_GH extends PhoneMetadata
{
    protected const ID = 'GH';
    protected const COUNTRY_CODE = 233;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[235]\d{3}|800)\d{5}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:[0346-9]\d|5[67])|5(?:[03-7]\d|9[1-9]))\d{6}')
            ->setExampleNumber('231234567')
            ->setPossibleLength([9]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3082[0-5]\d{4}|3(?:0(?:[237]\d|8[01])|[167](?:2[0-6]|7\d|80)|2(?:2[0-5]|7\d|80)|3(?:2[0-3]|7\d|80)|4(?:2[013-9]|3[01]|7\d|80)|5(?:2[0-7]|7\d|80)|8(?:2[0-2]|7\d|80)|9(?:[28]0|7\d))\d{5}')
            ->setExampleNumber('302345678')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[237]|8[0-2]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[235]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{5}')
            ->setExampleNumber('80012345')
            ->setPossibleLength([8]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{5}')
            ->setPossibleLength([8]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[235]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
