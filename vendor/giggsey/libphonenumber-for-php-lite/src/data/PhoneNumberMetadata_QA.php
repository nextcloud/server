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
class PhoneNumberMetadata_QA extends PhoneMetadata
{
    protected const ID = 'QA';
    protected const COUNTRY_CODE = 974;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{4}|(?:2|800)\d{6}|(?:0080|[3-7])\d{7}')
            ->setPossibleLength([7, 8, 9, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[35-7]\d{7}')
            ->setExampleNumber('33123456')
            ->setPossibleLength([8]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4(?:1111|2022)\d{3}|4(?:[04]\d\d|14[0-6]|999)\d{4}')
            ->setExampleNumber('44123456')
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['2[136]|8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[3-7]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{4}|(?:0080[01]|800)\d{6}')
            ->setExampleNumber('8001234')
            ->setPossibleLength([7, 9, 11]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2[136]\d{5}')
            ->setExampleNumber('2123456')
            ->setPossibleLength([7]);
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
