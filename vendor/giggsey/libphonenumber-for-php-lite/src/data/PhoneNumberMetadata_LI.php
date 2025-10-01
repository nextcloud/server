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
class PhoneNumberMetadata_LI extends PhoneMetadata
{
    protected const ID = 'LI';
    protected const COUNTRY_CODE = 423;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '(1001)|0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[68]\d{8}|(?:[2378]\d|90)\d{5}')
            ->setPossibleLength([7, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:6(?:(?:4[5-9]|5[0-46-9])\d|6(?:[024-6]\d|[17]0|3[7-9]))\d|7(?:[37-9]\d|42|56))\d{4}')
            ->setExampleNumber('660234567');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90(?:02[258]|1(?:23|3[14])|66[136])\d\d')
            ->setExampleNumber('9002222')
            ->setPossibleLength([7]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:01|1[27]|2[024]|3\d|6[02-578]|96)|3(?:[24]0|33|7[0135-7]|8[048]|9[0269]))\d{4}')
            ->setExampleNumber('2345678')
            ->setPossibleLength([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2379]|8(?:0[09]|7)', '[2379]|8(?:0(?:02|9)|7)'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['69'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['6'])
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8002[28]\d\d|80(?:05\d|9)\d{4}')
            ->setExampleNumber('8002222');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('870(?:28|87)\d\d')
            ->setExampleNumber('8702812')
            ->setPossibleLength([7]);
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('697(?:42|56|[78]\d)\d{4}')
            ->setExampleNumber('697861234')
            ->setPossibleLength([9]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
