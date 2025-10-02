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
class ShortNumberMetadata_ET extends PhoneMetadata
{
    protected const ID = 'ET';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9\d\d?')
            ->setPossibleLength([2, 3]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:07|11?|2|39?|9[17])')
            ->setExampleNumber('91');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:11?|2|39?|9[17])')
            ->setExampleNumber('91');
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:07|11?|2|39?|45|9[17])')
            ->setExampleNumber('91');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
