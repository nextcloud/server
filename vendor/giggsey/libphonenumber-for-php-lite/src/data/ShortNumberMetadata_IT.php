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
class ShortNumberMetadata_IT extends PhoneMetadata
{
    protected const ID = 'IT';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[14]\d{2,6}')
            ->setPossibleLength([3, 4, 5, 6, 7]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:12|4(?:[478](?:[0-4]|[5-9]\d\d)|55))\d\d')
            ->setExampleNumber('1200')
            ->setPossibleLength([4, 5, 7]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:[2358]|6\d{3})|87)')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[2358]')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0\d{2,3}|1(?:[2-57-9]|6(?:000|111))|3[39]|4(?:82|9\d{1,3})|5(?:00|1[58]|2[25]|3[03]|44|[59])|60|8[67]|9(?:[01]|2[2-9]|4\d|696))|4(?:2323|5045)|(?:1(?:2|92[01])|4(?:3(?:[01]|[45]\d\d)|[478](?:[0-4]|[5-9]\d\d)|55))\d\d')
            ->setExampleNumber('112');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4(?:3(?:[01]|[45]\d\d)|[478](?:[0-4]|[5-9]\d\d)|5[05])\d\d')
            ->setExampleNumber('43000')
            ->setPossibleLength([5, 7]);
    }
}
