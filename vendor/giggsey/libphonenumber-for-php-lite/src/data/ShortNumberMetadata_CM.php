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
class ShortNumberMetadata_CM extends PhoneMetadata
{
    protected const ID = 'CM';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[18]\d{1,3}')
            ->setPossibleLength([2, 3, 4]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[37]|[37])')
            ->setExampleNumber('13')
            ->setPossibleLength([2, 3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[37]|[37])')
            ->setExampleNumber('13')
            ->setPossibleLength([2, 3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[37]|[37])|8711')
            ->setExampleNumber('13');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('871\d')
            ->setExampleNumber('8710')
            ->setPossibleLength([4]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('871\d')
            ->setExampleNumber('8710')
            ->setPossibleLength([4]);
    }
}
