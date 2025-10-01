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
class PhoneNumberMetadata_HU extends PhoneMetadata
{
    protected const ID = 'HU';
    protected const COUNTRY_CODE = 36;
    protected const NATIONAL_PREFIX = '06';

    protected ?string $nationalPrefixForParsing = '06';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[235-7]\d{8}|[1-9]\d{7}')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[257]0|3[01])\d{7}')
            ->setExampleNumber('201234567')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9[01]\d{6}')
            ->setExampleNumber('90123456')
            ->setPossibleLength([8]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1\d|[27][2-9]|3[2-7]|4[24-9]|5[2-79]|6[23689]|8[2-57-9]|9[2-69])\d{6}')
            ->setExampleNumber('12345678')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixFormattingRule('(06 $1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[27][2-9]|3[2-7]|4[24-9]|5[2-79]|6|8[2-57-9]|9[2-69]'])
                ->setNationalPrefixFormattingRule('(06 $1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-9]'])
                ->setNationalPrefixFormattingRule('06 $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[48]0\d|680[29])\d{5}')
            ->setExampleNumber('80123456');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('21\d{7}')
            ->setExampleNumber('211234567')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('38\d{7}')
            ->setExampleNumber('381234567')
            ->setPossibleLength([9]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[48]0\d|680[29])\d{5}');
    }
}
