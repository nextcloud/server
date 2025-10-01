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
class PhoneNumberMetadata_HR extends PhoneMetadata
{
    protected const ID = 'HR';
    protected const COUNTRY_CODE = 385;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-69]\d{8}|80\d{5,7}|[1-79]\d{7}|6\d{6}')
            ->setPossibleLengthLocalOnly([6])
            ->setPossibleLength([7, 8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:(?:0[1-9]|[12589]\d)\d\d|7(?:[0679]\d\d|5(?:[01]\d|44|55|77|9[5-79])))\d{4}|98\d{6}')
            ->setExampleNumber('921234567')
            ->setPossibleLength([8, 9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6[01459]\d{6}|6[01]\d{5}')
            ->setExampleNumber('6001234')
            ->setPossibleLength([7, 8]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d{7}|(?:2[0-3]|3[1-5]|4[02-47-9]|5[1-3])\d{6,7}')
            ->setExampleNumber('12345678')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([8, 9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['6[01]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2,3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['6|7[245]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-57]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{5,7}')
            ->setExampleNumber('800123456');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7[45]\d{6}')
            ->setExampleNumber('74123456')
            ->setPossibleLength([8]);
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('62\d{6,7}|72\d{6}')
            ->setExampleNumber('62123456')
            ->setPossibleLength([8, 9]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
