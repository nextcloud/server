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
class PhoneNumberMetadata_FO extends PhoneMetadata
{
    protected const ID = 'FO';
    protected const COUNTRY_CODE = 298;

    protected ?string $nationalPrefixForParsing = '(10(?:01|[12]0|88))';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-9]\d{5}')
            ->setPossibleLength([6]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[27][1-9]|5\d|9[16])\d{4}')
            ->setExampleNumber('211234');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90(?:[13-5][15-7]|2[125-7]|9\d)\d\d')
            ->setExampleNumber('901123');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:20|[34]\d|8[19])\d{4}')
            ->setExampleNumber('201234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{6})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['[2-9]'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[257-9]\d{3}')
            ->setExampleNumber('802123');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:6[0-36]|88)\d{4}')
            ->setExampleNumber('601234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
