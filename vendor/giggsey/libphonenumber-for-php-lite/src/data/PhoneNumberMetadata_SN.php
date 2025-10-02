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
class PhoneNumberMetadata_SN extends PhoneMetadata
{
    protected const ID = 'SN';
    protected const COUNTRY_CODE = 221;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[378]\d|93)\d{7}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7(?:(?:[06-8]\d|[19]0|21)\d|5(?:0[01]|[19]0|2[25]|3[356]|[4-7]\d|8[35]))\d{5}')
            ->setExampleNumber('701234567');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('88[4689]\d{6}')
            ->setExampleNumber('884123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3(?:0(?:1[0-2]|80)|282|3(?:8[1-9]|9[3-9])|611)\d{5}')
            ->setExampleNumber('301012345');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[379]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6}')
            ->setExampleNumber('800123456');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('81[02468]\d{6}')
            ->setExampleNumber('810123456');
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3(?:392|9[01]\d)\d|93(?:3[13]0|929))\d{4}')
            ->setExampleNumber('933301234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
