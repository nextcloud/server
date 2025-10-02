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
class ShortNumberMetadata_CA extends PhoneMetadata
{
    protected const ID = 'CA';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-9]\d\d(?:\d{2,3})?')
            ->setPossibleLength([3, 5, 6]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|988|[29]11')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|911')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-35-9]\d{4,5}|112|[2-8]11|9(?:11|88)')
            ->setExampleNumber('112');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[235-7]11')
            ->setExampleNumber('211')
            ->setPossibleLength([3]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-35-9]\d{4,5}')
            ->setExampleNumber('10000')
            ->setPossibleLength([5, 6]);
    }
}
