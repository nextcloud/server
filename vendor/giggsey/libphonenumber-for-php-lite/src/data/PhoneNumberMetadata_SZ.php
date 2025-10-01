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
class PhoneNumberMetadata_SZ extends PhoneMetadata
{
    protected const ID = 'SZ';
    protected const COUNTRY_CODE = 268;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0800\d{4}|(?:[237]\d|900)\d{6}')
            ->setPossibleLength([8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7[6-9]\d{6}')
            ->setExampleNumber('76123456')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900\d{6}')
            ->setExampleNumber('900012345')
            ->setPossibleLength([9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[23][2-5]\d{6}')
            ->setExampleNumber('22171234')
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[0237]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0800\d{4}')
            ->setExampleNumber('08001234')
            ->setPossibleLength([8]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70\d{6}')
            ->setExampleNumber('70012345')
            ->setPossibleLength([8]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0800\d{4}')
            ->setPossibleLength([8]);
    }
}
