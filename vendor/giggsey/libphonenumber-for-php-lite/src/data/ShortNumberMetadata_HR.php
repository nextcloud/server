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
class ShortNumberMetadata_HR extends PhoneMetadata
{
    protected const ID = 'HR';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[016-9]\d{1,5}')
            ->setPossibleLength([2, 3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('06\d|(?:118|[6-8]\d{3})\d\d')
            ->setExampleNumber('060')
            ->setPossibleLength([3, 5, 6]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|9[2-4])|9[34]|1(?:16\d|39)\d\d')
            ->setExampleNumber('93')
            ->setPossibleLength([2, 3, 5, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|9[2-4])|9[34]')
            ->setExampleNumber('93')
            ->setPossibleLength([2, 3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:06|[6-8]\d{4})\d|1(?:1(?:2|6(?:00[06]|1(?:1[17]|23))|8\d\d)|3977|9(?:[2-5]|87))|9[34]')
            ->setExampleNumber('93');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('139\d\d')
            ->setExampleNumber('13900')
            ->setPossibleLength([5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('139\d\d')
            ->setExampleNumber('13900')
            ->setPossibleLength([5]);
    }
}
