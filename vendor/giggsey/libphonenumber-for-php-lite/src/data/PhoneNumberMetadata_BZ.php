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
class PhoneNumberMetadata_BZ extends PhoneMetadata
{
    protected const ID = 'BZ';
    protected const COUNTRY_CODE = 501;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:0800\d|[2-8])\d{6}')
            ->setPossibleLength([7, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6[0-35-7]\d{5}')
            ->setExampleNumber('6221234')
            ->setPossibleLength([7]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:[02]\d|36|[68]0)|[3-58](?:[02]\d|[68]0)|7(?:[02]\d|32|[68]0))\d{4}')
            ->setExampleNumber('2221234')
            ->setPossibleLength([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[2-8]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{4})(\d{3})')
                ->setFormat('$1-$2-$3-$4')
                ->setLeadingDigitsPattern(['0'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0800\d{7}')
            ->setExampleNumber('08001234123')
            ->setPossibleLength([11]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
