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
class PhoneNumberMetadata_NC extends PhoneMetadata
{
    protected const ID = 'NC';
    protected const COUNTRY_CODE = 687;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:050|[2-57-9]\d\d)\d{3}')
            ->setPossibleLength([6]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[579]\d|8[0-79])\d{4}')
            ->setExampleNumber('751234');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('36\d{4}')
            ->setExampleNumber('366711');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2[03-9]|3[0-5]|4[1-7]|88)\d{4}')
            ->setExampleNumber('201234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['5[6-8]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1.$2.$3')
                ->setLeadingDigitsPattern(['[02-57-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('050\d{3}')
            ->setExampleNumber('050012');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1.$2.$3')
                ->setLeadingDigitsPattern(['[02-57-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
