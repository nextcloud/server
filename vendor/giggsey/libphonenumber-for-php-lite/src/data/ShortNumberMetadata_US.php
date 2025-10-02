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
class ShortNumberMetadata_US extends PhoneMetadata
{
    protected const ID = 'US';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-9]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('24280|(?:381|968)35|4(?:3355|7553|8221)|5(?:(?:489|934)2|5928)|72078|(?:323|960)40|(?:276|414)63|(?:2(?:520|744)|7390|9968)9|(?:693|732|976)88|(?:3(?:556|825)|5294|8623|9729)4|(?:3378|4136|7642|8961|9979)6|(?:4(?:6(?:15|32)|827)|(?:591|720)8|9529)7')
            ->setExampleNumber('24280')
            ->setPossibleLength([5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|611|9(?:11|33|88)')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|911')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11(?:2|5[1-47]|[68]\d|7[0-57]|98)|[2-9]\d{3,5}|[2-8]11|9(?:11|33|88)')
            ->setExampleNumber('112');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:3333|(?:4224|7562|900)2|56447|6688)|3(?:1010|2665|7404)|40404|560560|6(?:0060|22639|5246|7622)|7(?:0701|3822|4666)|8(?:(?:3825|7226)5|4816)|99099')
            ->setExampleNumber('23333')
            ->setPossibleLength([5, 6]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('336\d\d|[2-9]\d{3}|[2356]11')
            ->setExampleNumber('211')
            ->setPossibleLength([3, 4, 5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-9]\d{4,5}')
            ->setExampleNumber('20000')
            ->setPossibleLength([5, 6]);
    }
}
