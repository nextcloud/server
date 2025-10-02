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
class ShortNumberMetadata_ES extends PhoneMetadata
{
    protected const ID = 'ES';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[0-379]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[12]2\d{1,4}|90(?:5\d|7)|(?:118|2(?:[357]\d|80)|3[357]\d)\d\d|[79]9[57]\d{3}')
            ->setExampleNumber('120');
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:16|6[57]|8[58])|1(?:006|12|[3-7]\d\d)|(?:116|20\d)\d{3}')
            ->setExampleNumber('016')
            ->setPossibleLength([3, 4, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('08[58]|112')
            ->setExampleNumber('085')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:1[0-26]|6[0-257]|8[058]|9[12])|1(?:0[03-57]\d{1,3}|1(?:2|6(?:000|111)|8\d\d)|2\d{1,4}|[3-9]\d\d)|2(?:2\d{1,4}|80\d\d)|90(?:5[124578]|7)|1(?:3[34]|77)|(?:2[01]\d|[79]9[57])\d{3}|[23][357]\d{3}')
            ->setExampleNumber('010');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0(?:[16][0-2]|80|9[12])|21\d{4}')
            ->setExampleNumber('010')
            ->setPossibleLength([3, 6]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:3[34]|77)|[12]2\d{1,4}')
            ->setExampleNumber('120');
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2[0-2]\d|3[357]|[79]9[57])\d{3}|2(?:[2357]\d|80)\d\d')
            ->setExampleNumber('22000')
            ->setPossibleLength([5, 6]);
    }
}
