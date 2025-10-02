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
class PhoneNumberMetadata_CY extends PhoneMetadata
{
    protected const ID = 'CY';
    protected const COUNTRY_CODE = 357;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[279]\d|[58]0)\d{6}')
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:10|[4-79]\d)\d{5}')
            ->setExampleNumber('96123456');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90[09]\d{5}')
            ->setExampleNumber('90012345');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2[2-6]\d{6}')
            ->setExampleNumber('22345678');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[257-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{5}')
            ->setExampleNumber('80001234');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[1-9]\d{5}')
            ->setExampleNumber('80112345');
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('700\d{5}')
            ->setExampleNumber('70012345');
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:50|77)\d{6}')
            ->setExampleNumber('77123456');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
