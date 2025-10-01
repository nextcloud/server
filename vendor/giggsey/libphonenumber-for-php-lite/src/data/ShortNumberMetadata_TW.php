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
class ShortNumberMetadata_TW extends PhoneMetadata
{
    protected const ID = 'TW';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d{2,3}')
            ->setPossibleLength([3, 4]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('10[56]')
            ->setExampleNumber('105')
            ->setPossibleLength([3]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[0289]|1(?:81|92)\d')
            ->setExampleNumber('110');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[029]')
            ->setExampleNumber('110')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[04-6]|1[0237-9]|3[389]|6[05-8]|7[07]|8(?:0|11)|9(?:19|22|5[057]|68|8[05]|9[15689]))')
            ->setExampleNumber('100');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:65|9(?:1\d|50|85|98))')
            ->setExampleNumber('165');
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = PhoneNumberDesc::empty();
    }
}
