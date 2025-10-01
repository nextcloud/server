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
class PhoneNumberMetadata_BH extends PhoneMetadata
{
    protected const ID = 'BH';
    protected const COUNTRY_CODE = 973;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[136-9]\d{7}')
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3(?:[0-79]\d|8[0-57-9])\d|6(?:3(?:00|33|6[16])|441|6(?:3[03-9]|[69]\d|7[0-689])))\d{4}')
            ->setExampleNumber('36001234');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:87|9[0-8])\d{6}')
            ->setExampleNumber('90123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:3[1356]|6[0156]|7\d)\d|6(?:1[16]\d|500|6(?:0\d|3[12]|44|55|7[7-9]|88)|9[69][69])|7(?:[07]\d\d|1(?:11|78)))\d{4}')
            ->setExampleNumber('17001234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[13679]|8[02-4679]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8[02369]\d{6}')
            ->setExampleNumber('80123456');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('84\d{6}')
            ->setExampleNumber('84123456');
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
