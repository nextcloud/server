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
class PhoneNumberMetadata_LU extends PhoneMetadata
{
    protected const ID = 'LU';
    protected const COUNTRY_CODE = 352;

    protected ?string $nationalPrefixForParsing = '(15(?:0[06]|1[12]|[35]5|4[04]|6[26]|77|88|99)\d)';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('35[013-9]\d{4,8}|6\d{8}|35\d{2,4}|(?:[2457-9]\d|3[0-46-9])\d{2,9}')
            ->setPossibleLength([4, 5, 6, 7, 8, 9, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6(?:[269][18]|5[1568]|7[189]|81)\d{6}')
            ->setExampleNumber('628123456')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90[015]\d{5}')
            ->setExampleNumber('90012345')
            ->setPossibleLength([8]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:35[013-9]|80[2-9]|90[89])\d{1,8}|(?:2[2-9]|3[0-46-9]|[457]\d|8[13-9]|9[2-579])\d{2,9}')
            ->setExampleNumber('27123456');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['2(?:0[2-689]|[2-9])|[3-57]|8(?:0[2-9]|[13-9])|9(?:0[89]|[2-579])'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2(?:0[2-689]|[2-9])|[3-57]|8(?:0[2-9]|[13-9])|9(?:0[89]|[2-579])'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['20[2-689]'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})(\d{1,2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['2(?:[0367]|4[3-8])'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['80[01]|90[015]'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['20'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['6'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})(\d{2})(\d{1,2})')
                ->setFormat('$1 $2 $3 $4 $5')
                ->setLeadingDigitsPattern(['2(?:[0367]|4[3-8])'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})(\d{1,5})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[3-57]|8[13-9]|9(?:0[89]|[2-579])|(?:2|80)[2-9]'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{5}')
            ->setExampleNumber('80012345')
            ->setPossibleLength([8]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('801\d{5}')
            ->setExampleNumber('80112345')
            ->setPossibleLength([8]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('20(?:1\d{5}|[2-689]\d{1,7})')
            ->setExampleNumber('20201234')
            ->setPossibleLength([4, 5, 6, 7, 8, 9, 10]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
