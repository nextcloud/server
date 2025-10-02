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
class PhoneNumberMetadata_MA extends PhoneMetadata
{
    protected const ID = 'MA';
    protected const COUNTRY_CODE = 212;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mainCountryForCode = true;
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[5-8]\d{8}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:6(?:[0-79]\d|8[0-247-9])|7(?:[0167]\d|2[0-8]|5[0-3]|8[0-7]))\d{6}')
            ->setExampleNumber('650123456');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('89\d{7}')
            ->setExampleNumber('891234567');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5(?:2(?:[0-25-79]\d|3[1-578]|4[02-46-8]|8[0235-7])|3(?:[0-47]\d|5[02-9]|6[02-8]|8[014-9]|9[3-9])|(?:4[067]|5[03])\d)\d{5}')
            ->setExampleNumber('520123456');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['5[45]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{5})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['5(?:2[2-46-9]|3[3-9]|9)|8(?:0[89]|92)'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{7})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{6})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[5-7]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[0-7]\d{6}')
            ->setExampleNumber('801234567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:592(?:4[0-2]|93)|80[89]\d\d)\d{4}')
            ->setExampleNumber('592401234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
