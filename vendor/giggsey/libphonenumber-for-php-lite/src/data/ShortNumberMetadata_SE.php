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
class ShortNumberMetadata_SE extends PhoneMetadata
{
    protected const ID = 'SE';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-37-9]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11811[89]|72\d{3}')
            ->setExampleNumber('72000')
            ->setPossibleLength([5, 6]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11(?:2|(?:3|6\d)\d\d|414|77)|900\d\d')
            ->setExampleNumber('112');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|90000')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 5]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11(?:[25]|313|6(?:00[06]|1(?:1[17]|23))|7[0-8])|2(?:2[02358]|33|4[01]|50|6[1-4])|32[13]|8(?:22|88)|9(?:0(?:00|51)0|12)|(?:11(?:4|8[02-46-9])|7\d\d|90[2-4])\d\d|(?:118|90)1(?:[02-9]\d|1[013-9])')
            ->setExampleNumber('112');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:2[02358]|33|4[01]|50|6[1-4])|32[13]|8(?:22|88)|912')
            ->setExampleNumber('220')
            ->setPossibleLength([3]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7\d{4}')
            ->setExampleNumber('70000')
            ->setPossibleLength([5]);
    }
}
