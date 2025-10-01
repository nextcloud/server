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
class PhoneNumberMetadata_870 extends PhoneMetadata
{
    protected const ID = '001';
    protected const COUNTRY_CODE = 870;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7\d{11}|[235-7]\d{8}')
            ->setPossibleLength([9, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[356]|774[45])\d{8}|7[6-8]\d{7}')
            ->setExampleNumber('301234567');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = PhoneNumberDesc::empty();
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[235-7]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2\d{8}')
            ->setExampleNumber('201234567')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
