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
class ShortNumberMetadata_LR extends PhoneMetadata
{
    protected const ID = 'LR';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[3489]\d{2,3}')
            ->setPossibleLength([3, 4]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('355|911')
            ->setExampleNumber('355')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('355|911')
            ->setExampleNumber('355')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('355|4040|8(?:400|933)|911')
            ->setExampleNumber('355');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:404|8(?:40|93))\d')
            ->setExampleNumber('4040')
            ->setPossibleLength([4]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:404|8(?:40|93))\d')
            ->setExampleNumber('4040')
            ->setPossibleLength([4]);
    }
}
