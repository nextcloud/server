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
class ShortNumberMetadata_DZ extends PhoneMetadata
{
    protected const ID = 'DZ';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[17]\d{1,3}')
            ->setPossibleLength([2, 3, 4]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|[47]|54\d)')
            ->setExampleNumber('14');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:12|[47])')
            ->setExampleNumber('14')
            ->setPossibleLength([2, 3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:055|12|[47]|548)|730')
            ->setExampleNumber('14');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('730')
            ->setExampleNumber('730')
            ->setPossibleLength([3]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('730')
            ->setExampleNumber('730')
            ->setPossibleLength([3]);
    }
}
