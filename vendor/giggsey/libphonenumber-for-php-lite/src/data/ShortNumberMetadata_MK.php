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
class ShortNumberMetadata_MK extends PhoneMetadata
{
    protected const ID = 'MK';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d\d(?:\d(?:\d{2})?)?')
            ->setPossibleLength([3, 4, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:2|6\d{3})|9[2-4])')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|9[2-4])')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:2|8\d)|3\d|9[2-4])|1(?:16|2\d)\d{3}')
            ->setExampleNumber('112');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
