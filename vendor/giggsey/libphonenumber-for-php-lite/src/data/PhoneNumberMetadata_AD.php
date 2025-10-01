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
class PhoneNumberMetadata_AD extends PhoneMetadata
{
    protected const ID = 'AD';
    protected const COUNTRY_CODE = 376;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1|6\d)\d{7}|[135-9]\d{5}')
            ->setPossibleLength([6, 8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('690\d{6}|[356]\d{5}')
            ->setExampleNumber('312345')
            ->setPossibleLength([6, 9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[19]\d{5}')
            ->setExampleNumber('912345')
            ->setPossibleLength([6]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[78]\d{5}')
            ->setExampleNumber('712345')
            ->setPossibleLength([6]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[135-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['6'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('180[02]\d{4}')
            ->setExampleNumber('18001234')
            ->setPossibleLength([8]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1800\d{4}')
            ->setPossibleLength([8]);
    }
}
