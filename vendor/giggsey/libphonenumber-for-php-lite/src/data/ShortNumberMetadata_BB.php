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
class ShortNumberMetadata_BB extends PhoneMetadata
{
    protected const ID = 'BB';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-689]\d\d')
            ->setPossibleLength([3]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('988|[2359]11')
            ->setExampleNumber('211');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2359]11')
            ->setExampleNumber('211');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('988|[2-689]11')
            ->setExampleNumber('211');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[468]11')
            ->setExampleNumber('411');
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
