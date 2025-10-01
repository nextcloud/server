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
class PhoneNumberMetadata_VU extends PhoneMetadata
{
    protected const ID = 'VU';
    protected const COUNTRY_CODE = 678;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[57-9]\d{6}|(?:[238]\d|48)\d{3}')
            ->setPossibleLength([5, 7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[58]\d|7[013-7])\d{5}')
            ->setExampleNumber('5912345')
            ->setPossibleLength([7]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:38[0-8]|48[4-9])\d\d|(?:2[02-9]|3[4-7]|88)\d{3}')
            ->setExampleNumber('22123')
            ->setPossibleLength([5]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[57-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('81[18]\d\d')
            ->setExampleNumber('81123')
            ->setPossibleLength([5]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:0[1-9]|1[01])\d{4}')
            ->setExampleNumber('9010123')
            ->setPossibleLength([7]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3[03]|900\d)\d{3}')
            ->setExampleNumber('30123');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
