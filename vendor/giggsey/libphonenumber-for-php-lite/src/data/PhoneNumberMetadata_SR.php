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
class PhoneNumberMetadata_SR extends PhoneMetadata
{
    protected const ID = 'SR';
    protected const COUNTRY_CODE = 597;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[2-5]|68|[78]\d|90)\d{5}')
            ->setPossibleLength([6, 7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:7[124-7]|8[1-9])\d{5}')
            ->setExampleNumber('7412345')
            ->setPossibleLength([7]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90\d{5}')
            ->setExampleNumber('9012345')
            ->setPossibleLength([7]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2[1-3]|3[0-7]|(?:4|68)\d|5[2-58])\d{4}')
            ->setExampleNumber('211234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['56'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[2-5]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[6-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{5}')
            ->setExampleNumber('8012345')
            ->setPossibleLength([7]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('56\d{4}')
            ->setExampleNumber('561234')
            ->setPossibleLength([6]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
