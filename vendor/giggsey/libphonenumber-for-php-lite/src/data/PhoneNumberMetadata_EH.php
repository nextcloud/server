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
class PhoneNumberMetadata_EH extends PhoneMetadata
{
    protected const ID = 'EH';
    protected const COUNTRY_CODE = 212;
    protected const LEADING_DIGITS = '528[89]';
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[5-8]\d{8}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:6(?:[0-79]\d|8[0-247-9])|7(?:[0167]\d|2[0-8]|5[0-3]|8[0-7]))\d{6}')
            ->setExampleNumber('650123456');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('89\d{7}')
            ->setExampleNumber('891234567');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('528[89]\d{5}')
            ->setExampleNumber('528812345');
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[0-7]\d{6}')
            ->setExampleNumber('801234567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:592(?:4[0-2]|93)|80[89]\d\d)\d{4}')
            ->setExampleNumber('592401234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
