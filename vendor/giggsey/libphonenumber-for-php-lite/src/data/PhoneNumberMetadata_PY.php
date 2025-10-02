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
class PhoneNumberMetadata_PY extends PhoneMetadata
{
    protected const ID = 'PY';
    protected const COUNTRY_CODE = 595;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('59\d{4,6}|9\d{5,10}|(?:[2-46-8]\d|5[0-8])\d{4,7}')
            ->setPossibleLengthLocalOnly([5])
            ->setPossibleLength([6, 7, 8, 9, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:51|6[129]|7[1-6]|8[1-7]|9[1-5])\d{6}')
            ->setExampleNumber('961456789')
            ->setPossibleLength([9]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[26]1|3[289]|4[1246-8]|7[1-3]|8[1-36])\d{5,7}|(?:2(?:2[4-68]|[4-68]\d|7[15]|9[1-5])|3(?:18|3[167]|4[2357]|51|[67]\d)|4(?:3[12]|5[13]|9[1-47])|5(?:[1-4]\d|5[02-4])|6(?:3[1-3]|44|7[1-8])|7(?:4[0-4]|5\d|6[1-578]|75|8[0-8])|858)\d{5,6}')
            ->setExampleNumber('212345678')
            ->setPossibleLengthLocalOnly([5, 6])
            ->setPossibleLength([7, 8, 9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[2-9]0'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[26]1|3[289]|4[1246-8]|7[1-3]|8[1-36]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['2[279]|3[13-5]|4[359]|5|6(?:[34]|7[1-46-8])|7[46-8]|85'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2[14-68]|3[26-9]|4[1246-8]|6(?:1|75)|7[1-35]|8[1-36]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['87'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['9(?:[5-79]|8[1-7])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-8]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9800\d{5,7}')
            ->setExampleNumber('98000123456')
            ->setPossibleLength([9, 10, 11]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8700[0-4]\d{4}')
            ->setExampleNumber('870012345')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-9]0\d{4,7}')
            ->setExampleNumber('201234567')
            ->setPossibleLength([6, 7, 8, 9]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
