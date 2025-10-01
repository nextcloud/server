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
class ShortNumberMetadata_BD extends PhoneMetadata
{
    protected const ID = 'BD';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1579]\d{2,4}')
            ->setPossibleLength([3, 4, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('10[0-26]|[19]99')
            ->setExampleNumber('100')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('10[0-2]|[19]99')
            ->setExampleNumber('100')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0(?:[0-369]|5[1-4]|7[0-4]|8[0-29])|1[16-9]|2(?:[134]|2[0-5])|3(?:1\d?|6[3-6])|5[2-9])|5012|786|9594|[19]99|1(?:0(?:50|6\d)|33|4(?:0|1\d))\d')
            ->setExampleNumber('100');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:11|2[13])|(?:501|959)\d|786')
            ->setExampleNumber('111')
            ->setPossibleLength([3, 4]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('959\d')
            ->setExampleNumber('9590')
            ->setPossibleLength([4]);
    }
}
