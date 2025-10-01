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
class PhoneNumberMetadata_SV extends PhoneMetadata
{
    protected const ID = 'SV';
    protected const COUNTRY_CODE = 503;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[267]\d{7}|(?:80\d|900)\d{4}(?:\d{4})?')
            ->setPossibleLength([7, 8, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[67]\d{7}')
            ->setExampleNumber('70123456')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900\d{4}(?:\d{4})?')
            ->setExampleNumber('9001234')
            ->setPossibleLength([7, 11]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:79(?:0[0347-9]|[1-9]\d)|89(?:0[024589]|[1-9]\d))\d{3}|2(?:[1-69]\d|[78][0-8])\d{5}')
            ->setExampleNumber('21234567')
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[89]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[267]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[89]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{8}|80[01]\d{4}')
            ->setExampleNumber('8001234')
            ->setPossibleLength([7, 11]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
