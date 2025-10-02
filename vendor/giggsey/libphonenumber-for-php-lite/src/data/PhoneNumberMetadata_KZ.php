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
class PhoneNumberMetadata_KZ extends PhoneMetadata
{
    protected const ID = 'KZ';
    protected const COUNTRY_CODE = 7;
    protected const LEADING_DIGITS = '33622|7';
    protected const NATIONAL_PREFIX = '8';

    protected ?string $nationalPrefixForParsing = '8';
    protected ?string $internationalPrefix = '810';
    protected ?string $preferredInternationalPrefix = '8~10';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:33622|8\d{8})\d{5}|[78]\d{9}')
            ->setPossibleLengthLocalOnly([5, 6, 7])
            ->setPossibleLength([10, 14]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7(?:0[0-25-8]|47|6[0-4]|7[15-8]|85)\d{7}')
            ->setExampleNumber('7710009998')
            ->setPossibleLength([10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('809\d{7}')
            ->setExampleNumber('8091234567')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:33622|7(?:1(?:0(?:[23]\d|4[0-3]|59|63)|1(?:[23]\d|4[0-79]|59)|2(?:[23]\d|59)|3(?:2\d|3[0-79]|4[0-35-9]|59)|4(?:[24]\d|3[013-9]|5[1-9]|97)|5(?:2\d|3[1-9]|4[0-7]|59)|6(?:[2-4]\d|5[19]|61)|72\d|8(?:[27]\d|3[1-46-9]|4[0-5]|59))|2(?:1(?:[23]\d|4[46-9]|5[3469])|2(?:2\d|3[0679]|46|5[12679])|3(?:[2-4]\d|5[139])|4(?:2\d|3[1-35-9]|59)|5(?:[23]\d|4[0-8]|59|61)|6(?:2\d|3[1-9]|4[0-4]|59)|7(?:[2379]\d|40|5[279])|8(?:[23]\d|4[0-3]|59)|9(?:2\d|3[124578]|59))))\d{5}')
            ->setExampleNumber('7123456789')
            ->setPossibleLengthLocalOnly([5, 6, 7])
            ->setPossibleLength([10]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:00|108\d{3})\d{7}')
            ->setExampleNumber('8001234567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('808\d{7}')
            ->setExampleNumber('8081234567')
            ->setPossibleLength([10]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('751\d{7}')
            ->setExampleNumber('7511234567')
            ->setPossibleLength([10]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('751\d{7}')
            ->setPossibleLength([10]);
    }
}
