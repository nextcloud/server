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
class PhoneNumberMetadata_LV extends PhoneMetadata
{
    protected const ID = 'LV';
    protected const COUNTRY_CODE = 371;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[268]\d|90)\d{6}')
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2333[0-8]\d{3}|2(?:[0-24-9]\d\d|3(?:0[07]|[14-9]\d|2[02-9]|3[0-24-9]))\d{4}')
            ->setExampleNumber('21234567');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90\d{6}')
            ->setExampleNumber('90123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6\d{7}')
            ->setExampleNumber('63123456');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[269]|8[01]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{6}')
            ->setExampleNumber('80123456');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('81\d{6}')
            ->setExampleNumber('81123456');
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
