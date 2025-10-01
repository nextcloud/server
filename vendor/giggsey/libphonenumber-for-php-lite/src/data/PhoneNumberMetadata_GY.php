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
class PhoneNumberMetadata_GY extends PhoneMetadata
{
    protected const ID = 'GY';
    protected const COUNTRY_CODE = 592;

    protected ?string $internationalPrefix = '001';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[2-8]\d{3}|9008)\d{3}')
            ->setPossibleLength([7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:510|6\d\d|7(?:[0-5]\d|6[019]|70))\d{4}')
            ->setExampleNumber('6091234');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9008\d{3}')
            ->setExampleNumber('9008123');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:1[6-9]|2[0-35-9]|3[1-4]|5[3-9]|6\d|7[0-79])|3(?:2[25-9]|3\d)|4(?:4[0-24]|5[56])|50[0-6]|77[1-57])\d{4}')
            ->setExampleNumber('2201234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[2-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:289|8(?:00|6[28]|88|99))\d{4}')
            ->setExampleNumber('2891234');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('515\d{4}')
            ->setExampleNumber('5151234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
