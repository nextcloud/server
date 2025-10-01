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
class PhoneNumberMetadata_CH extends PhoneMetadata
{
    protected const ID = 'CH';
    protected const COUNTRY_CODE = 41;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8\d{11}|[2-9]\d{8}')
            ->setPossibleLength([9, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:6[89]|7[235-9])\d{7}')
            ->setExampleNumber('781234567')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90[016]\d{6}')
            ->setExampleNumber('900123456')
            ->setPossibleLength([9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2[12467]|3[1-4]|4[134]|5[256]|6[12]|[7-9]1)\d{7}')
            ->setExampleNumber('212345678')
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8[047]|90'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[2-79]|81'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4 $5')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([9]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('84[0248]\d{6}')
            ->setExampleNumber('840123456')
            ->setPossibleLength([9]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('878\d{6}')
            ->setExampleNumber('878123456')
            ->setPossibleLength([9]);
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('74[0248]\d{6}')
            ->setExampleNumber('740123456')
            ->setPossibleLength([9]);
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5[18]\d{7}')
            ->setExampleNumber('581234567')
            ->setPossibleLength([9]);
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('860\d{9}')
            ->setExampleNumber('860123456789')
            ->setPossibleLength([12]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
