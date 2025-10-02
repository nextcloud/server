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
class ShortNumberMetadata_GI extends PhoneMetadata
{
    protected const ID = 'GI';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[158]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8[1-69]\d\d')
            ->setExampleNumber('8100')
            ->setPossibleLength([4]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:00|1[25]|23|4(?:1|7\d)|5[15]|9[02-49])|555|(?:116\d|80)\d\d')
            ->setExampleNumber('100')
            ->setPossibleLength([3, 4, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|9[09])')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:00|1(?:[25]|6(?:00[06]|1(?:1[17]|23))|8\d\d)|23|4(?:1|7[014])|5[015]|9[02-49])|555|8[0-79]\d\d|8(?:00|4[0-2]|8[0-589])')
            ->setExampleNumber('100');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('150|87\d\d')
            ->setExampleNumber('150')
            ->setPossibleLength([3, 4]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:00|1(?:5|8\d\d)|23|51|9[2-4])|555|8(?:00|4[0-2]|8[0-589])')
            ->setExampleNumber('100')
            ->setPossibleLength([3, 5]);
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
