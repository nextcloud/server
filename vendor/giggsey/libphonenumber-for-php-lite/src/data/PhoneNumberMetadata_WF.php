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
class PhoneNumberMetadata_WF extends PhoneMetadata
{
    protected const ID = 'WF';
    protected const COUNTRY_CODE = 681;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:40|72|8\d{4})\d{4}|[89]\d{5}')
            ->setPossibleLength([6, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:72|8[23])\d{4}')
            ->setExampleNumber('821234')
            ->setPossibleLength([6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('72\d{4}')
            ->setExampleNumber('721234')
            ->setPossibleLength([6]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[47-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[0-5]\d{6}')
            ->setExampleNumber('800012345')
            ->setPossibleLength([9]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9[23]\d{4}')
            ->setExampleNumber('921234')
            ->setPossibleLength([6]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[48]0\d{4}')
            ->setExampleNumber('401234')
            ->setPossibleLength([6]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
