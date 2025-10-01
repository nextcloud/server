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
class PhoneNumberMetadata_MV extends PhoneMetadata
{
    protected const ID = 'MV';
    protected const COUNTRY_CODE = 960;

    protected ?string $internationalPrefix = '0(?:0|19)';
    protected ?string $preferredInternationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:800|9[0-57-9]\d)\d{7}|[34679]\d{6}')
            ->setPossibleLength([7, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:46[46]|[79]\d\d)\d{4}')
            ->setExampleNumber('7712345')
            ->setPossibleLength([7]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900\d{7}')
            ->setExampleNumber('9001234567')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3(?:0[0-4]|3[0-59])|6(?:[58][024689]|6[024-68]|7[02468]))\d{4}')
            ->setExampleNumber('6701234')
            ->setPossibleLength([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[34679]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[89]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{7}')
            ->setExampleNumber('8001234567')
            ->setPossibleLength([10]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4(?:0[01]|50)\d{4}')
            ->setExampleNumber('4001234')
            ->setPossibleLength([7]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
