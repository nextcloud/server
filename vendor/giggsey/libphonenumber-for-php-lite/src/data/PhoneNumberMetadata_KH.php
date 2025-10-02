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
class PhoneNumberMetadata_KH extends PhoneMetadata
{
    protected const ID = 'KH';
    protected const COUNTRY_CODE = 855;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00[14-9]';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d{9}|[1-9]\d{7,8}')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([8, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:(?:1[28]|3[18]|9[67])\d|6[016-9]|7(?:[07-9]|[16]\d)|8(?:[013-79]|8\d))\d{6}|(?:1\d|9[0-57-9])\d{6}|(?:2[3-6]|3[2-6]|4[2-4]|[5-7][2-5])48\d{5}')
            ->setExampleNumber('91234567')
            ->setPossibleLength([8, 9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1900(?:1\d|2[09])\d{4}')
            ->setExampleNumber('1900123456')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('23(?:4(?:[2-4]|[56]\d)|[568]\d\d)\d{4}|23[236-9]\d{5}|(?:2[4-6]|3[2-6]|4[2-4]|[5-7][2-5])(?:(?:[237-9]|4[56]|5\d)\d{5}|6\d{5,6})')
            ->setExampleNumber('23756789')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([8, 9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[1-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1800(?:1\d|2[019])\d{4}')
            ->setExampleNumber('1800123456')
            ->setPossibleLength([10]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
