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
class PhoneNumberMetadata_RO extends PhoneMetadata
{
    protected const ID = 'RO';
    protected const COUNTRY_CODE = 40;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected ?string $preferredExtnPrefix = ' int ';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[236-8]\d|90)\d{7}|[23]\d{5}')
            ->setPossibleLength([6, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:630|702)0\d{5}|(?:6(?:00|2\d)|7(?:0[013-9]|1[0-3]|[2-7]\d|8[03-8]|9[0-39]))\d{6}')
            ->setExampleNumber('712034567')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90[0136]\d{6}')
            ->setExampleNumber('900123456')
            ->setPossibleLength([9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[23][13-6]\d{7}|(?:2(?:19\d|[3-6]\d9)|31\d\d)\d\d')
            ->setExampleNumber('211234567');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['2[3-6]', '2[3-6]\d9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['219|31'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[23]1'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[236-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([9]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('801\d{6}')
            ->setExampleNumber('801123456')
            ->setPossibleLength([9]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:37\d|80[578])\d{6}')
            ->setExampleNumber('372123456')
            ->setPossibleLength([9]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
