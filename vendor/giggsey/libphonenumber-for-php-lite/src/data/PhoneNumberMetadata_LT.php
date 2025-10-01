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
class PhoneNumberMetadata_LT extends PhoneMetadata
{
    protected const ID = 'LT';
    protected const COUNTRY_CODE = 370;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '[08]';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[3469]\d|52|[78]0)\d{6}')
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6\d{7}')
            ->setExampleNumber('61234567');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:0[0239]|10)\d{5}')
            ->setExampleNumber('90012345');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3[1478]|4[124-6]|52)\d{6}')
            ->setExampleNumber('31234567');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['52[0-7]'])
                ->setNationalPrefixFormattingRule('(0-$1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[7-9]'])
                ->setNationalPrefixFormattingRule('0 $1')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['37|4(?:[15]|6[1-8])'])
                ->setNationalPrefixFormattingRule('(0-$1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[3-6]'])
                ->setNationalPrefixFormattingRule('(0-$1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[02]\d{5}')
            ->setExampleNumber('80012345');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('808\d{5}')
            ->setExampleNumber('80812345');
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70[05]\d{5}')
            ->setExampleNumber('70012345');
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[89]01\d{5}')
            ->setExampleNumber('80123456');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70[67]\d{5}')
            ->setExampleNumber('70712345');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
