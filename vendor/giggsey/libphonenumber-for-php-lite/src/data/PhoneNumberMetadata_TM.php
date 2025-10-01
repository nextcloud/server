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
class PhoneNumberMetadata_TM extends PhoneMetadata
{
    protected const ID = 'TM';
    protected const COUNTRY_CODE = 993;
    protected const NATIONAL_PREFIX = '8';

    protected ?string $nationalPrefixForParsing = '8';
    protected ?string $internationalPrefix = '810';
    protected ?string $preferredInternationalPrefix = '8~10';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[1-6]\d|71)\d{6}')
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:6\d|71)\d{6}')
            ->setExampleNumber('66123456');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:2\d|3[1-9])|2(?:22|4[0-35-8])|3(?:22|4[03-9])|4(?:22|3[128]|4\d|6[15])|5(?:22|5[7-9]|6[014-689]))\d{5}')
            ->setExampleNumber('12345678');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2-$3-$4')
                ->setLeadingDigitsPattern(['12'])
                ->setNationalPrefixFormattingRule('(8 $1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d)(\d{2})(\d{2})')
                ->setFormat('$1 $2-$3-$4')
                ->setLeadingDigitsPattern(['[1-5]'])
                ->setNationalPrefixFormattingRule('(8 $1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[67]'])
                ->setNationalPrefixFormattingRule('8 $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
