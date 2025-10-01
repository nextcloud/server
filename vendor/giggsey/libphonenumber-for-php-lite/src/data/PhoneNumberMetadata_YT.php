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
class PhoneNumberMetadata_YT extends PhoneMetadata
{
    protected const ID = 'YT';
    protected const COUNTRY_CODE = 262;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7093\d{5}|(?:80|9\d)\d{7}|(?:26|63)9\d{6}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:639(?:0[0-79]|1[019]|[267]\d|3[09]|40|5[05-9]|9[04-79])|7093[5-7])\d{4}')
            ->setExampleNumber('639012345');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('269(?:0[0-467]|15|5[0-4]|6\d|[78]0)\d{4}')
            ->setExampleNumber('269601234');
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{7}')
            ->setExampleNumber('801234567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:(?:39|47)8[01]|769\d)\d{4}')
            ->setExampleNumber('939801234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
