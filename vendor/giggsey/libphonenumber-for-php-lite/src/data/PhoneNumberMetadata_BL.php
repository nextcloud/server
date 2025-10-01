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
class PhoneNumberMetadata_BL extends PhoneMetadata
{
    protected const ID = 'BL';
    protected const COUNTRY_CODE = 590;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:590\d|7090)\d{5}|(?:69|80|9\d)\d{7}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:69(?:0\d\d|1(?:2[2-9]|3[0-5])|4(?:0[89]|1[2-6]|9\d)|6(?:1[016-9]|5[0-4]|[67]\d))|7090[0-4])\d{4}')
            ->setExampleNumber('690001234');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('590(?:2[7-9]|3[3-7]|5[12]|87)\d{4}')
            ->setExampleNumber('590271234');
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[0-5]\d{6}')
            ->setExampleNumber('800012345');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:(?:39[5-7]|76[018])\d|475[0-6])\d{4}')
            ->setExampleNumber('976012345');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
