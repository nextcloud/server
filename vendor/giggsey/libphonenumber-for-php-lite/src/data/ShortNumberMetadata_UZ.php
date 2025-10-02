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
class ShortNumberMetadata_UZ extends PhoneMetadata
{
    protected const ID = 'UZ';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[04]\d(?:\d(?:\d{2})?)?')
            ->setPossibleLength([2, 3, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:0[1-3]|[1-3]|50)')
            ->setExampleNumber('01')
            ->setPossibleLength([2, 3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:0[1-3]|[1-3]|50)')
            ->setExampleNumber('01')
            ->setPossibleLength([2, 3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:0[1-3]|[1-3]|50)|45400')
            ->setExampleNumber('01');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('454\d\d')
            ->setExampleNumber('45400')
            ->setPossibleLength([5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('454\d\d')
            ->setExampleNumber('45400')
            ->setPossibleLength([5]);
    }
}
