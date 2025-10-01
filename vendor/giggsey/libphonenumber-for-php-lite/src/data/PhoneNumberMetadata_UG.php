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
class PhoneNumberMetadata_UG extends PhoneMetadata
{
    protected const ID = 'UG';
    protected const COUNTRY_CODE = 256;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00[057]';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6}|(?:[29]0|[347]\d)\d{7}')
            ->setPossibleLengthLocalOnly([5, 6, 7])
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('72[48]0\d{5}|7(?:[014-8]\d|2[067]|36|9[0189])\d{6}')
            ->setExampleNumber('712345678');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90[1-3]\d{6}')
            ->setExampleNumber('901123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('20(?:(?:240|30[67])\d|6(?:00[0-2]|30[0-4]))\d{3}|(?:20(?:[017]\d|2[5-9]|3[1-4]|5[0-4]|6[15-9])|[34]\d{3})\d{5}')
            ->setExampleNumber('312345678')
            ->setPossibleLengthLocalOnly([5, 6, 7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['202', '2024'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[27-9]|4(?:6[45]|[7-9])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[34]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800[1-3]\d{5}')
            ->setExampleNumber('800123456');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
