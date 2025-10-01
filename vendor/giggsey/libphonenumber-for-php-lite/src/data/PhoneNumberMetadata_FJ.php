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
class PhoneNumberMetadata_FJ extends PhoneMetadata
{
    protected const ID = 'FJ';
    protected const COUNTRY_CODE = 679;

    protected ?string $internationalPrefix = '0(?:0|52)';
    protected ?string $preferredInternationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('45\d{5}|(?:0800\d|[235-9])\d{6}')
            ->setPossibleLength([7, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[279]\d|45|5[01568]|8[034679])\d{5}')
            ->setExampleNumber('7012345')
            ->setPossibleLength([7]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('603\d{4}|(?:3[0-5]|6[25-7]|8[58])\d{5}')
            ->setExampleNumber('3212345')
            ->setPossibleLength([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[235-9]|45'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0800\d{7}')
            ->setExampleNumber('08001234567')
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
