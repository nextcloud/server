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
class PhoneNumberMetadata_CX extends PhoneMetadata
{
    protected const ID = 'CX';
    protected const COUNTRY_CODE = 61;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '([59]\d{7})$|0';
    protected ?string $internationalPrefix = '001[14-689]|14(?:1[14]|34|4[17]|[56]6|7[47]|88)0011';
    protected ?string $preferredInternationalPrefix = '0011';
    protected ?string $nationalPrefixTransformRule = '8$1';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:[0-79]\d{8}(?:\d{2})?|8[0-24-9]\d{7})|[148]\d{8}|1\d{5,7}')
            ->setPossibleLength([6, 7, 8, 9, 10, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4(?:79[01]|83[0-389]|94[0-478])\d{5}|4(?:[0-36]\d|4[047-9]|5[0-25-9]|7[02-8]|8[0-24-9]|9[0-37-9])\d{6}')
            ->setExampleNumber('412345678')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('190[0-26]\d{6}')
            ->setExampleNumber('1900123456')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:51(?:0(?:01|30|59|88)|1(?:17|46|75)|2(?:22|35))|91(?:00[6-9]|1(?:[28]1|49|78)|2(?:09|63)|3(?:12|26|75)|4(?:56|97)|64\d|7(?:0[01]|1[0-2])|958))\d{3}')
            ->setExampleNumber('891641234')
            ->setPossibleLengthLocalOnly([8])
            ->setPossibleLength([9]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('180(?:0\d{3}|2)\d{3}')
            ->setExampleNumber('1800123456')
            ->setPossibleLength([7, 10]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('13(?:00\d{6}(?:\d{2})?|45[0-4]\d{3})|13\d{4}')
            ->setExampleNumber('1300123456')
            ->setPossibleLength([6, 8, 10, 12]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('14(?:5(?:1[0458]|[23][458])|71\d)\d{4}')
            ->setExampleNumber('147101234')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
