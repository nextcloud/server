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
class PhoneNumberMetadata_KW extends PhoneMetadata
{
    protected const ID = 'KW';
    protected const COUNTRY_CODE = 965;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('18\d{5}|(?:[2569]\d|41)\d{6}')
            ->setPossibleLength([7, 8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:41\d\d|5(?:(?:[05]\d|1[0-7]|6[56])\d|2(?:22|5[25])|7(?:55|77)|88[58])|6(?:(?:0[034679]|5[015-9]|6\d)\d|1(?:00|11|6[16])|2[26]2|3[36]3|4[46]4|7(?:0[013-9]|[67]\d)|8[68]8|9(?:[069]\d|3[039]))|9(?:(?:[04679]\d|8[057-9])\d|1(?:00|1[01]|99)|2(?:00|2\d)|3(?:00|3[03])|5(?:00|5\d)))\d{4}')
            ->setExampleNumber('50012345')
            ->setPossibleLength([8]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:[23]\d\d|4(?:[1-35-9]\d|44)|5(?:0[034]|[2-46]\d|5[1-3]|7[1-7]))\d{4}')
            ->setExampleNumber('22345678')
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3,4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[169]|2(?:[235]|4[1-35-9])|52'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[245]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('18\d{5}')
            ->setExampleNumber('1801234')
            ->setPossibleLength([7]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
