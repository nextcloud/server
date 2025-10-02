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
class ShortNumberMetadata_ZW extends PhoneMetadata
{
    protected const ID = 'ZW';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[139]\d\d(?:\d{2})?')
            ->setPossibleLength([3, 5]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3[013-57-9]\d{3}')
            ->setExampleNumber('30000')
            ->setPossibleLength([5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|9(?:5[023]|61|9[3-59])')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|99[3-59]')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[2469]|3[013-57-9]\d{3}|9(?:5[023]|6[0-25]|9[3-59])')
            ->setExampleNumber('112');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('114|9(?:5[023]|6[0-25])')
            ->setExampleNumber('114')
            ->setPossibleLength([3]);
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
