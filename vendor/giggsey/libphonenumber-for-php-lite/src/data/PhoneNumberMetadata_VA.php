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
class PhoneNumberMetadata_VA extends PhoneMetadata
{
    protected const ID = 'VA';
    protected const COUNTRY_CODE = 39;
    protected const LEADING_DIGITS = '06698';

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0\d{5,10}|3[0-8]\d{7,10}|55\d{8}|8\d{5}(?:\d{2,4})?|(?:1\d|39)\d{7,8}')
            ->setPossibleLength([6, 7, 8, 9, 10, 11, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3[1-9]\d{8}|3[2-9]\d{7}')
            ->setExampleNumber('3123456789')
            ->setPossibleLength([9, 10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:0878\d{3}|89(?:2\d|3[04]|4(?:[0-4]|[5-9]\d\d)|5[0-4]))\d\d|(?:1(?:44|6[346])|89(?:38|5[5-9]|9))\d{6}')
            ->setExampleNumber('899123456')
            ->setPossibleLength([6, 8, 9, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('06698\d{1,6}')
            ->setExampleNumber('0669812345')
            ->setPossibleLength([6, 7, 8, 9, 10, 11]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80(?:0\d{3}|3)\d{3}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([6, 9]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('84(?:[08]\d{3}|[17])\d{3}')
            ->setExampleNumber('848123456')
            ->setPossibleLength([6, 9]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:78\d|99)\d{6}')
            ->setExampleNumber('1781234567')
            ->setPossibleLength([9, 10]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('55\d{8}')
            ->setExampleNumber('5512345678')
            ->setPossibleLength([10]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3[2-8]\d{9,10}')
            ->setExampleNumber('33101234501')
            ->setPossibleLength([11, 12]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
