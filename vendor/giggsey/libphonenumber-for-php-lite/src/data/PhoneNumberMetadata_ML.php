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
class PhoneNumberMetadata_ML extends PhoneMetadata
{
    protected const ID = 'ML';
    protected const COUNTRY_CODE = 223;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[24-9]\d{7}')
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:0(?:01|79)|17\d)\d{4}|(?:5[0-3]|[679]\d|8[2-59])\d{6}')
            ->setExampleNumber('65012345');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:07[0-8]|12[67])\d{4}|(?:2(?:02|1[4-689])|4(?:0[0-4]|4[1-59]))\d{5}')
            ->setExampleNumber('20212345');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['67[057-9]|74[045]', '67(?:0[09]|[59]9|77|8[89])|74(?:0[02]|44|55)'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[24-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{6}')
            ->setExampleNumber('80012345');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{6}');
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[24-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
