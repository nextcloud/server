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
class PhoneNumberMetadata_AL extends PhoneMetadata
{
    protected const ID = 'AL';
    protected const COUNTRY_CODE = 355;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:700\d\d|900)\d{3}|8\d{5,7}|(?:[2-5]|6\d)\d{7}')
            ->setPossibleLengthLocalOnly([5])
            ->setPossibleLength([6, 7, 8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6(?:[78][2-9]|9\d)\d{6}')
            ->setExampleNumber('672123456')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900[1-9]\d\d')
            ->setExampleNumber('900123')
            ->setPossibleLength([6]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4505[0-2]\d{3}|(?:[2358][16-9]\d[2-9]|4410)\d{4}|(?:[2358][2-5][2-9]|4(?:[2-57-9][2-9]|6\d))\d{5}')
            ->setExampleNumber('22345678')
            ->setPossibleLengthLocalOnly([5, 6, 7])
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['80|9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['4[2-6]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2358][2-5]|4'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[23578]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['6'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{4}')
            ->setExampleNumber('8001234')
            ->setPossibleLength([7]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('808[1-9]\d\d')
            ->setExampleNumber('808123')
            ->setPossibleLength([6]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('700[2-9]\d{4}')
            ->setExampleNumber('70021234')
            ->setPossibleLength([8]);
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
