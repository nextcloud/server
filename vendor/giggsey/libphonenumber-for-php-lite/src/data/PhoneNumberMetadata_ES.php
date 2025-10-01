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
class PhoneNumberMetadata_ES extends PhoneMetadata
{
    protected const ID = 'ES';
    protected const COUNTRY_CODE = 34;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[5-9]\d{8}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:590[16]00\d|9(?:6906(?:09|10)|7390\d\d))\d\d|(?:6\d|7[1-48])\d{7}')
            ->setExampleNumber('612345678');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[367]\d{6}')
            ->setExampleNumber('803123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('96906(?:0[0-8]|1[1-9]|[2-9]\d)\d\d|9(?:69(?:0[0-57-9]|[1-9]\d)|73(?:[0-8]\d|9[1-9]))\d{4}|(?:8(?:[1356]\d|[28][0-8]|[47][1-9])|9(?:[135]\d|[268][0-8]|4[1-9]|7[124-9]))\d{6}')
            ->setExampleNumber('810123456');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['905'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{6})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['[79]9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[89]00'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[5-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[89]00\d{6}')
            ->setExampleNumber('800123456');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90[12]\d{6}')
            ->setExampleNumber('901123456');
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70\d{7}')
            ->setExampleNumber('701234567');
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('51\d{7}')
            ->setExampleNumber('511234567');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[89]00'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[5-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
