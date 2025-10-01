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
class PhoneNumberMetadata_ST extends PhoneMetadata
{
    protected const ID = 'ST';
    protected const COUNTRY_CODE = 239;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:22|9\d)\d{5}')
            ->setPossibleLength([7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900[5-9]\d{3}|9(?:0[1-9]|[89]\d)\d{4}')
            ->setExampleNumber('9812345');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('22\d{5}')
            ->setExampleNumber('2221234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[29]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
