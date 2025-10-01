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
class PhoneNumberMetadata_SI extends PhoneMetadata
{
    protected const ID = 'SI';
    protected const COUNTRY_CODE = 386;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00|10(?:22|66|88|99)';
    protected ?string $preferredInternationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-7]\d{7}|8\d{4,7}|90\d{4,6}')
            ->setPossibleLength([5, 6, 7, 8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('65(?:[178]\d|5[56]|6[01])\d{4}|(?:[37][01]|4[0139]|51|6[489])\d{6}')
            ->setExampleNumber('31234567')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('89[1-3]\d{2,5}|90\d{4,6}')
            ->setExampleNumber('90123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[1-357][2-8]|4[24-8])\d{6}')
            ->setExampleNumber('12345678')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['8[09]|9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['59|8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[37][01]|4[0139]|51|6'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[1-57]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{4,6}')
            ->setExampleNumber('80123456')
            ->setPossibleLength([6, 7, 8]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:59\d\d|8(?:1(?:[67]\d|8[0-589])|2(?:0\d|2[0-37-9]|8[0-2489])|3[389]\d))\d{4}')
            ->setExampleNumber('59012345')
            ->setPossibleLength([8]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
