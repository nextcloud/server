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
class ShortNumberMetadata_IR extends PhoneMetadata
{
    protected const ID = 'IR';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[129]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[0-68]|2[0-59]|9[0-579])|911')
            ->setExampleNumber('110')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[025]|25)|911')
            ->setExampleNumber('110')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[0-68]|2[0-59]|3[346-8]|4(?:[0147]|[289]0)|5(?:0[14]|1[02479]|2[0-3]|39|[49]0|65)|6(?:[16]6|[27]|90)|8(?:03|1[18]|22|3[37]|4[28]|88|99)|9[0-579])|20(?:[09]0|1(?:[038]|1[079]|26|9[69])|2[01])|9(?:11|9(?:0009|90))')
            ->setExampleNumber('110');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:5[0-469]|8[0-489])\d')
            ->setExampleNumber('1500')
            ->setPossibleLength([4]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:5[0-469]|8[0-489])|99(?:0\d\d|9))\d')
            ->setExampleNumber('1500')
            ->setPossibleLength([4, 6]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('990\d{3}')
            ->setExampleNumber('990000')
            ->setPossibleLength([6]);
    }
}
