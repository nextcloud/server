<?php

/**
 * libphonenumber-for-php-lite data file
 * This file has been @generated from libphonenumber data
 * Do not modify!
 * @internal
 */

declare(strict_types=1);

namespace libphonenumber\data;

use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumberDesc;

/**
 * @internal
 */
class ShortNumberMetadata_BE extends PhoneMetadata
{
    protected const ID = 'BE';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-9]\d\d(?:\d(?:\d{2})?)?')
            ->setPossibleLength([3, 4, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:2[03]|40)4|(?:1(?:[24]1|3[01])|[2-79]\d\d)\d')
            ->setExampleNumber('1204')
            ->setPossibleLength([4]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[0-35-8]|1[0269]|7(?:12|77)|813)|(?:116|8)\d{3}')
            ->setExampleNumber('100');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[01]|12)')
            ->setExampleNumber('100')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[0-8]|16117|2(?:12|3[0-24])|313|414|5(?:1[05]|5[15]|66|95)|6(?:1[167]|36|6[16])|7(?:[07][017]|1[27-9]|22|33|65)|81[39])|[2-9]\d{3}|11[02679]|1(?:1600|45)0|1(?:[2-4]9|78)9|1[2-4]0[47]')
            ->setExampleNumber('100');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-9]\d{3}')
            ->setExampleNumber('2000')
            ->setPossibleLength([4]);
    }
}
