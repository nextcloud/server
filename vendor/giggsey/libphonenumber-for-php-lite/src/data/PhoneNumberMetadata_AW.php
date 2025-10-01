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
class PhoneNumberMetadata_AW extends PhoneMetadata
{
    protected const ID = 'AW';
    protected const COUNTRY_CODE = 297;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[25-79]\d\d|800)\d{4}')
            ->setPossibleLength([7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:290|5[69]\d|6(?:[03]0|22|4[0-2]|[69]\d)|7(?:[34]\d|7[07])|9(?:6[45]|9[4-8]))\d{4}')
            ->setExampleNumber('5601234');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900\d{4}')
            ->setExampleNumber('9001234');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5(?:2\d|8[1-9])\d{4}')
            ->setExampleNumber('5212345');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[25-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{4}')
            ->setExampleNumber('8001234');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:28\d|501)\d{4}')
            ->setExampleNumber('5011234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
