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
class ShortNumberMetadata_SL extends PhoneMetadata
{
    protected const ID = 'SL';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[069]\d\d(?:\d{2})?')
            ->setPossibleLength([3, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:01|99)9')
            ->setExampleNumber('019')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:01|99)9')
            ->setExampleNumber('019')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:01|99)9|60400')
            ->setExampleNumber('019');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('604\d\d')
            ->setExampleNumber('60400')
            ->setPossibleLength([5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('604\d\d')
            ->setExampleNumber('60400')
            ->setPossibleLength([5]);
    }
}
