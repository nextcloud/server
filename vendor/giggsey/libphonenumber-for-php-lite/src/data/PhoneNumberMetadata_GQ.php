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
class PhoneNumberMetadata_GQ extends PhoneMetadata
{
    protected const ID = 'GQ';
    protected const COUNTRY_CODE = 240;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('222\d{6}|(?:3\d|55|[89]0)\d{7}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:222|55\d)\d{6}')
            ->setExampleNumber('222123456');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90\d[1-9]\d{5}')
            ->setExampleNumber('900123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('33[0-24-9]\d[46]\d{4}|3(?:33|5\d)\d[7-9]\d{4}')
            ->setExampleNumber('333091234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[235]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[89]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d[1-9]\d{5}')
            ->setExampleNumber('800123456');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
