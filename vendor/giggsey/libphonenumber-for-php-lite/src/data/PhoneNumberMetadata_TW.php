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
class PhoneNumberMetadata_TW extends PhoneMetadata
{
    protected const ID = 'TW';
    protected const COUNTRY_CODE = 886;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '0(?:0[25-79]|19)';
    protected ?string $preferredExtnPrefix = '#';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-689]\d{8}|7\d{9,10}|[2-8]\d{7}|2\d{6}')
            ->setPossibleLength([7, 8, 9, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:40001[0-2]|9[0-8]\d{4})\d{3}')
            ->setExampleNumber('912345678')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('20(?:[013-9]\d\d|2)\d{4}')
            ->setExampleNumber('203123456')
            ->setPossibleLength([7, 9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2[2-8]\d|370|55[01]|7[1-9])\d{6}|4(?:(?:0(?:0[1-9]|[2-48]\d)|1[023]\d)\d{4,5}|(?:[239]\d\d|4(?:0[56]|12|49))\d{5})|6(?:[01]\d{7}|4(?:0[56]|12|24|4[09])\d{4,5})|8(?:(?:2(?:3\d|4[0-269]|[578]0|66)|36[24-9]|90\d\d)\d{4}|4(?:0[56]|12|24|4[09])\d{4,5})|(?:2(?:2(?:0\d\d|4(?:0[68]|[249]0|3[0-467]|5[0-25-9]|6[0235689]))|(?:3(?:[09]\d|1[0-4])|(?:4\d|5[0-49]|6[0-29]|7[0-5])\d)\d)|(?:(?:3[2-9]|5[2-8]|6[0-35-79]|8[7-9])\d\d|4(?:2(?:[089]\d|7[1-9])|(?:3[0-4]|[78]\d|9[01])\d))\d)\d{3}')
            ->setExampleNumber('221234567')
            ->setPossibleLength([8, 9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d)(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['202'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[258]0'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[23568]|4(?:0[02-48]|[1-47-9])|7[1-9]', '[23568]|4(?:0[2-48]|[1-47-9])|(?:400|7)[1-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[49]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4,5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[0-79]\d{6}|800\d{5}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([8, 9]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('99\d{7}')
            ->setExampleNumber('990123456')
            ->setPossibleLength([9]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7010(?:[0-2679]\d|3[0-7]|8[0-5])\d{5}|70\d{8}')
            ->setExampleNumber('7012345678')
            ->setPossibleLength([10, 11]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('50[0-46-9]\d{6}')
            ->setExampleNumber('500123456')
            ->setPossibleLength([9]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
