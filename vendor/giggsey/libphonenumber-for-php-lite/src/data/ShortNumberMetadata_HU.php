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
class ShortNumberMetadata_HU extends PhoneMetadata
{
    protected const ID = 'HU';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[457]|12|4[0-4]\d)|1(?:16\d|37|45)\d\d')
            ->setExampleNumber('104');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[457]|12)')
            ->setExampleNumber('104')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[457]|1(?:2|6(?:000|1(?:11|23))|800)|2(?:0[0-4]|1[013489]|2[0-5]|3[0-46]|4[0-24-68]|5[0-2568]|6[06]|7[0-25-7]|8[028]|9[08])|37(?:00|37|7[07])|4(?:0[0-5]|1[013-8]|2[034]|3[23]|4[02-9]|5(?:00|41|67))|777|8(?:1[27-9]|2[04]|40|[589]))')
            ->setExampleNumber('104');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:4[0-4]|77)\d|1(?:18|2|45)\d\d')
            ->setExampleNumber('1200')
            ->setPossibleLength([4, 5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('184\d')
            ->setExampleNumber('1840')
            ->setPossibleLength([4]);
    }
}
