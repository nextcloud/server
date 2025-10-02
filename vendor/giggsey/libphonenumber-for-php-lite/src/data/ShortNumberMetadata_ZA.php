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
class ShortNumberMetadata_ZA extends PhoneMetadata
{
    protected const ID = 'ZA';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[134]\d{2,4}')
            ->setPossibleLength([3, 4, 5]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('41(?:348|851)')
            ->setExampleNumber('41348')
            ->setPossibleLength([5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:01\d\d|12)')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 5]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:01(?:11|77)|12)')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 5]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0(?:1(?:11|77)|20|7)|1[12]|77(?:3[237]|[45]7|6[279]|9[26]))|[34]\d{4}')
            ->setExampleNumber('107');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3(?:078[23]|7(?:064|567)|8126)|4(?:394[16]|7751|8837)|4[23]699')
            ->setExampleNumber('30782')
            ->setPossibleLength([5]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('111')
            ->setExampleNumber('111')
            ->setPossibleLength([3]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[34]\d{4}')
            ->setExampleNumber('30000')
            ->setPossibleLength([5]);
    }
}
