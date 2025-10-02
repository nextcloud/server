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
class PhoneNumberMetadata_IS extends PhoneMetadata
{
    protected const ID = 'IS';
    protected const COUNTRY_CODE = 354;

    protected ?string $internationalPrefix = '00|1(?:0(?:01|[12]0)|100)';
    protected ?string $preferredInternationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:38\d|[4-9])\d{6}')
            ->setPossibleLength([7, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:38[589]\d\d|6(?:1[1-8]|2[0-6]|3[026-9]|4[014679]|5[0159]|6[0-69]|70|8[06-8]|9\d)|7(?:5[057]|[6-9]\d)|8(?:2[0-59]|[3-69]\d|8[238]))\d{4}')
            ->setExampleNumber('6111234');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90(?:0\d|1[5-79]|2[015-79]|3[135-79]|4[125-7]|5[25-79]|7[1-37]|8[0-35-7])\d{3}')
            ->setExampleNumber('9001234')
            ->setPossibleLength([7]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:4(?:1[0-24-69]|2[0-7]|[37][0-8]|4[0-24589]|5[0-68]|6\d|8[0-36-8])|5(?:05|[156]\d|2[02578]|3[0-579]|4[03-7]|7[0-2578]|8[0-35-9]|9[013-689])|872)\d{4}')
            ->setExampleNumber('4101234')
            ->setPossibleLength([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[4-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['3'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[0-8]\d{4}')
            ->setExampleNumber('8001234')
            ->setPossibleLength([7]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('49[0-24-79]\d{4}')
            ->setExampleNumber('4921234')
            ->setPossibleLength([7]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('809\d{4}')
            ->setExampleNumber('8091234')
            ->setPossibleLength([7]);
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:689|8(?:7[18]|80)|95[48])\d{4}')
            ->setExampleNumber('6891234')
            ->setPossibleLength([7]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
