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
class PhoneNumberMetadata_SC extends PhoneMetadata
{
    protected const ID = 'SC';
    protected const COUNTRY_CODE = 248;

    protected ?string $internationalPrefix = '010|0[0-2]';
    protected ?string $preferredInternationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[2489]\d|64)\d{5}')
            ->setPossibleLength([7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2[125-8]\d{5}')
            ->setExampleNumber('2510123');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('85\d{5}')
            ->setExampleNumber('8512345');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4[2-46]\d{5}')
            ->setExampleNumber('4217123');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[246]|9[57]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800[08]\d{3}')
            ->setExampleNumber('8000000');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('971\d{4}|(?:64|95)\d{5}')
            ->setExampleNumber('6412345');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
