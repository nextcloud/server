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
class PhoneNumberMetadata_HN extends PhoneMetadata
{
    protected const ID = 'HN';
    protected const COUNTRY_CODE = 504;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8\d{10}|[237-9]\d{7}')
            ->setPossibleLength([8, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[37-9]\d{7}')
            ->setExampleNumber('91234567')
            ->setPossibleLength([8]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:2(?:0[0-59]|1[1-9]|[23]\d|4[02-7]|5[57]|6[245]|7[0135689]|8[01346-9]|9[0-2])|4(?:0[578]|2[3-59]|3[13-9]|4[0-68]|5[1-3589])|5(?:0[2357-9]|1[1-356]|4[03-5]|5\d|6[014-69]|7[04]|80)|6(?:[056]\d|17|2[067]|3[047]|4[0-378]|[78][0-8]|9[01])|7(?:0[5-79]|6[46-9]|7[02-9]|8[034]|91)|8(?:79|8[0-357-9]|9[1-57-9]))\d{4}')
            ->setExampleNumber('22123456')
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[237-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8002\d{7}')
            ->setExampleNumber('80021234567')
            ->setPossibleLength([11]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8002\d{7}')
            ->setPossibleLength([11]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[237-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
