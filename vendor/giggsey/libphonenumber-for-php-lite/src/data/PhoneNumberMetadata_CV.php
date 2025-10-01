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
class PhoneNumberMetadata_CV extends PhoneMetadata
{
    protected const ID = 'CV';
    protected const COUNTRY_CODE = 238;

    protected ?string $internationalPrefix = '0';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[2-59]\d\d|800)\d{4}')
            ->setPossibleLength([7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:36|5[1-389]|9\d)\d{5}')
            ->setExampleNumber('9911234');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:2[1-7]|3[0-8]|4[12]|5[1256]|6\d|7[1-3]|8[1-5])\d{4}')
            ->setExampleNumber('2211234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-589]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{4}')
            ->setExampleNumber('8001234');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3[3-5]|4[356])\d{5}')
            ->setExampleNumber('3401234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
