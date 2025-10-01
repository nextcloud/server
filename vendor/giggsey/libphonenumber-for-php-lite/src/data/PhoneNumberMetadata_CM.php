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
class PhoneNumberMetadata_CM extends PhoneMetadata
{
    protected const ID = 'CM';
    protected const COUNTRY_CODE = 237;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[26]\d{8}|88\d{6,7}')
            ->setPossibleLength([8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:24[23]|6(?:[25-9]\d|40))\d{6}')
            ->setExampleNumber('671234567')
            ->setPossibleLength([9]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:22|33)\d{6}')
            ->setExampleNumber('222123456')
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['88'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{2})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4 $5')
                ->setLeadingDigitsPattern(['[26]|88'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('88\d{6,7}')
            ->setExampleNumber('88012345');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
