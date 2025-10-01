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
class PhoneNumberMetadata_LS extends PhoneMetadata
{
    protected const ID = 'LS';
    protected const COUNTRY_CODE = 266;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[256]\d\d|800)\d{5}')
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[56]\d{7}')
            ->setExampleNumber('50123456');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2\d{7}')
            ->setExampleNumber('22123456');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[2568]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800[1256]\d{4}')
            ->setExampleNumber('80021234');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
