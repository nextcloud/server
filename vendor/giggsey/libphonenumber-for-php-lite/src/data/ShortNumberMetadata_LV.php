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
class ShortNumberMetadata_LV extends PhoneMetadata
{
    protected const ID = 'LV';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[018]\d{1,5}')
            ->setPossibleLength([2, 3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1180|821\d\d')
            ->setExampleNumber('1180')
            ->setPossibleLength([4, 5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0[1-3]|11(?:[023]|6\d{3})')
            ->setExampleNumber('01')
            ->setPossibleLength([2, 3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0[1-3]|11[023]')
            ->setExampleNumber('01')
            ->setPossibleLength([2, 3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0[1-4]|1(?:1(?:[02-4]|6(?:000|111)|8[0189])|(?:5|65)5|77)|821[57]4')
            ->setExampleNumber('01');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1181')
            ->setExampleNumber('1181')
            ->setPossibleLength([4]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('165\d')
            ->setExampleNumber('1650')
            ->setPossibleLength([4]);
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
