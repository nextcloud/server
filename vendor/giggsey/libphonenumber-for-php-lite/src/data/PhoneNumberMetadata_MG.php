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
class PhoneNumberMetadata_MG extends PhoneMetadata
{
    protected const ID = 'MG';
    protected const COUNTRY_CODE = 261;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '([24-9]\d{6})$|0';
    protected ?string $internationalPrefix = '00';
    protected ?string $nationalPrefixTransformRule = '20$1';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[23]\d{8}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3[2-47-9]\d{7}')
            ->setExampleNumber('321234567');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2072[29]\d{4}|20(?:2\d|4[47]|5[3467]|6[279]|7[356]|8[268]|9[2457])\d{5}')
            ->setExampleNumber('202123456')
            ->setPossibleLengthLocalOnly([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{3})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[23]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('22\d{7}')
            ->setExampleNumber('221234567');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
