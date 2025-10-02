<?php

/**
 * libphonenumber-for-php-lite data file
 * This file has been @generated from libphonenumber data
 * Do not modify!
 * @internal
 */

declare(strict_types=1);

namespace libphonenumber\data;

use libphonenumber\NumberFormat;
use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumberDesc;

/**
 * @internal
 */
class PhoneNumberMetadata_PA extends PhoneMetadata
{
    protected const ID = 'PA';
    protected const COUNTRY_CODE = 507;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:00800|8\d{3})\d{6}|[68]\d{7}|[1-57-9]\d{6}')
            ->setPossibleLength([7, 8, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1[16]1|21[89]|6\d{3}|8(?:1[01]|7[23]))\d{4}')
            ->setExampleNumber('61234567')
            ->setPossibleLength([7, 8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:8(?:22|55|60|7[78]|86)|9(?:00|81))\d{4}')
            ->setExampleNumber('8601234')
            ->setPossibleLength([7]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:0\d|1[479]|2[37]|3[0137]|4[17]|5[05]|6[058]|7[0167]|8[2358]|9[1389])|2(?:[0235-79]\d|1[0-7]|4[013-9]|8[02-9])|3(?:[047-9]\d|1[0-8]|2[0-5]|33|5[0-35]|6[068])|4(?:00|3[0-579]|4\d|7[0-57-9])|5(?:[01]\d|2[0-7]|[56]0|79)|7(?:0[09]|2[0-26-8]|3[03]|4[04]|5[05-9]|6[0156]|7[0-24-9]|8[4-9]|90)|8(?:09|2[89]|3\d|4[0-24-689]|5[014]|8[02])|9(?:0[5-9]|1[0135-8]|2[036-9]|3[35-79]|40|5[0457-9]|6[05-9]|7[04-9]|8[35-8]|9\d))\d{4}')
            ->setExampleNumber('2001234')
            ->setPossibleLength([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[1-57-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[68]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{4,5}|(?:00800|800\d)\d{6}')
            ->setExampleNumber('8001234');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
