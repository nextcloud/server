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
class PhoneNumberMetadata_PT extends PhoneMetadata
{
    protected const ID = 'PT';
    protected const COUNTRY_CODE = 351;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1693\d{5}|(?:[26-9]\d|30)\d{7}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6(?:[06]92(?:30|9\d)|[35]92(?:[049]\d|3[034]))\d{3}|(?:(?:16|6[0356])93|9(?:[1-36]\d\d|480))\d{5}')
            ->setExampleNumber('912345678');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:6(?:0[178]|4[68])\d|76(?:0[1-57]|1[2-47]|2[237]))\d{5}')
            ->setExampleNumber('760123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:[12]\d|3[1-689]|4[1-59]|[57][1-9]|6[1-35689]|8[1-69]|9[1256])\d{6}')
            ->setExampleNumber('212345678');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2[12]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['16|[236-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[02]\d{6}')
            ->setExampleNumber('800123456');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80(?:8\d|9[1579])\d{5}')
            ->setExampleNumber('808123456');
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('884[0-4689]\d{5}')
            ->setExampleNumber('884123456');
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('30\d{7}')
            ->setExampleNumber('301234567');
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6(?:222\d|89(?:00|88|99))\d{4}')
            ->setExampleNumber('622212345');
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70(?:38[01]|596|(?:7\d|8[17])\d)\d{4}')
            ->setExampleNumber('707123456');
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('600\d{6}|6[06]92(?:0\d|3[349]|49)\d{3}')
            ->setExampleNumber('600110000');
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
