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
class PhoneNumberMetadata_CZ extends PhoneMetadata
{
    protected const ID = 'CZ';
    protected const COUNTRY_CODE = 420;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[2-578]\d|60)\d{7}|9\d{8,11}')
            ->setPossibleLength([9, 10, 11, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:60[1-8]\d|7(?:0(?:[2-5]\d|60)|19[0-4]|[2379]\d\d))\d{5}')
            ->setExampleNumber('601123456')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:0[05689]|76)\d{6}')
            ->setExampleNumber('900123456')
            ->setPossibleLength([9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2\d|3[1257-9]|4[16-9]|5[13-9])\d{7}')
            ->setExampleNumber('212345678')
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-8]|9[015-7]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['96'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([9]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8[134]\d{7}')
            ->setExampleNumber('811234567')
            ->setPossibleLength([9]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70[01]\d{6}')
            ->setExampleNumber('700123456')
            ->setPossibleLength([9]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9[17]0\d{6}')
            ->setExampleNumber('910123456')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:5\d|7[2-4])\d{6}')
            ->setExampleNumber('972123456')
            ->setPossibleLength([9]);
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:3\d{9}|6\d{7,10})')
            ->setExampleNumber('93123456789');
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
