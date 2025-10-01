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
class ShortNumberMetadata_BH extends PhoneMetadata
{
    protected const ID = 'BH';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[0189]\d\d(?:\d{2})?')
            ->setPossibleLength([3, 5]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9[148]\d{3}')
            ->setExampleNumber('91000')
            ->setPossibleLength([5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:0[167]|81)\d{3}|[19]99')
            ->setExampleNumber('199');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[19]99')
            ->setExampleNumber('199')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:[02]\d|12|4[01]|51|8[18]|9[169])|99[02489]|(?:0[167]|8[158]|9[148])\d{3}')
            ->setExampleNumber('100');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0[67]\d{3}|88000|98555')
            ->setExampleNumber('06000')
            ->setPossibleLength([5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('88000|98555')
            ->setExampleNumber('88000')
            ->setPossibleLength([5]);
    }
}
