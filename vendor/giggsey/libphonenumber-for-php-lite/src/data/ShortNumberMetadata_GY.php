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
class ShortNumberMetadata_GY extends PhoneMetadata
{
    protected const ID = 'GY';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[019]\d{2,3}')
            ->setPossibleLength([3, 4]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('91[1-3]')
            ->setExampleNumber('911')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('91[1-3]')
            ->setExampleNumber('911')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:02|(?:17|80)1|444|7(?:[67]7|9)|9(?:0[78]|[2-47]))|1(?:443|5[568])|91[1-3]')
            ->setExampleNumber('002');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('144\d')
            ->setExampleNumber('1440')
            ->setPossibleLength([4]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('144\d')
            ->setExampleNumber('1440')
            ->setPossibleLength([4]);
    }
}
