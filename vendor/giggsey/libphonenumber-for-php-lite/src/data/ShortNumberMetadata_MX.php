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
class ShortNumberMetadata_MX extends PhoneMetadata
{
    protected const ID = 'MX';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[0579]\d{2,4}')
            ->setPossibleLength([3, 4, 5]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:530\d|776)\d')
            ->setExampleNumber('7760')
            ->setPossibleLength([4, 5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:6[0568]|80)|911')
            ->setExampleNumber('060')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:6[0568]|80)|911')
            ->setExampleNumber('060')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0[1-9]\d|53053|7766|911')
            ->setExampleNumber('010');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:[249]0|[35][01])')
            ->setExampleNumber('020')
            ->setPossibleLength([3]);
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
