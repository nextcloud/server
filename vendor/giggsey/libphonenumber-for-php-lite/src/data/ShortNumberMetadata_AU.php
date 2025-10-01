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
class ShortNumberMetadata_AU extends PhoneMetadata
{
    protected const ID = 'AU';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[0-27]\d{2,7}')
            ->setPossibleLength([3, 4, 5, 6, 7, 8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:2(?:34|456)|9\d{4,6})')
            ->setExampleNumber('1234')
            ->setPossibleLength([4, 5, 6, 7, 8]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('000|1(?:06|12|258885|55\d)|733')
            ->setExampleNumber('000')
            ->setPossibleLength([3, 4, 7]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('000|1(?:06|12)')
            ->setExampleNumber('000')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('000|1(?:06|1(?:00|2|9[46])|2(?:014[1-3]|[23]\d|(?:4|5\d)\d{2,3}|68[689]|72(?:20|3\d\d)|8(?:[013-9]\d|2))|555|9\d{4,6})|225|7(?:33|67)')
            ->setExampleNumber('000');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[09]\d|24733)|225|767')
            ->setExampleNumber('225')
            ->setPossibleLength([3, 4, 6]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:258885|55\d)')
            ->setExampleNumber('1550')
            ->setPossibleLength([4, 7]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('19\d{4,6}')
            ->setExampleNumber('190000')
            ->setPossibleLength([6, 7, 8]);
    }
}
