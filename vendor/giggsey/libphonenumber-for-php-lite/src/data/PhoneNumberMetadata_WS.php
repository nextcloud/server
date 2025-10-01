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
class PhoneNumberMetadata_WS extends PhoneMetadata
{
    protected const ID = 'WS';
    protected const COUNTRY_CODE = 685;

    protected ?string $internationalPrefix = '0';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[2-6]|8\d{5})\d{4}|[78]\d{6}|[68]\d{5}')
            ->setPossibleLength([5, 6, 7, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:7[1-35-8]|8(?:[3-7]|9\d{3}))\d{5}')
            ->setExampleNumber('7212345')
            ->setPossibleLength([7, 10]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6[1-9]\d{3}|(?:[2-5]|60)\d{4}')
            ->setExampleNumber('22123')
            ->setPossibleLength([5, 6]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{5})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['[2-5]|6[1-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[68]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{3}')
            ->setExampleNumber('800123')
            ->setPossibleLength([6]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
