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
class ShortNumberMetadata_KZ extends PhoneMetadata
{
    protected const ID = 'KZ';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-4]\d{2,4}')
            ->setPossibleLength([3, 4, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[1-3]|12)|212\d')
            ->setExampleNumber('101')
            ->setPossibleLength([3, 4]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[1-3]|12)')
            ->setExampleNumber('101')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[1-4]|12)|2121|(?:3040|404)0')
            ->setExampleNumber('101');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:304\d|404)\d')
            ->setExampleNumber('4040')
            ->setPossibleLength([4, 5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:304\d|404)\d')
            ->setExampleNumber('4040')
            ->setPossibleLength([4, 5]);
    }
}
