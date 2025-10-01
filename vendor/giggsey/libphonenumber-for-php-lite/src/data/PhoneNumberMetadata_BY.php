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
class PhoneNumberMetadata_BY extends PhoneMetadata
{
    protected const ID = 'BY';
    protected const COUNTRY_CODE = 375;
    protected const NATIONAL_PREFIX = '8';

    protected ?string $nationalPrefixForParsing = '0|80?';
    protected ?string $internationalPrefix = '810';
    protected ?string $preferredInternationalPrefix = '8~10';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[12]\d|33|44|902)\d{7}|8(?:0[0-79]\d{5,7}|[1-7]\d{9})|8(?:1[0-489]|[5-79]\d)\d{7}|8[1-79]\d{6,7}|8[0-79]\d{5}|8\d{5}')
            ->setPossibleLengthLocalOnly([5])
            ->setPossibleLength([6, 7, 8, 9, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:5[5-79]|9[1-9])|(?:33|44)\d)\d{6}')
            ->setExampleNumber('294911911')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:810|902)\d{7}')
            ->setExampleNumber('9021234567')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:5(?:1[1-5]|[24]\d|6[2-4]|9[1-7])|6(?:[235]\d|4[1-7])|7\d\d)|2(?:1(?:[246]\d|3[0-35-9]|5[1-9])|2(?:[235]\d|4[0-8])|3(?:[26]\d|3[02-79]|4[024-7]|5[03-7])))\d{5}')
            ->setExampleNumber('152450911')
            ->setPossibleLengthLocalOnly([5, 6, 7])
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['800'])
                ->setNationalPrefixFormattingRule('8 $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['800'])
                ->setNationalPrefixFormattingRule('8 $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{2})(\d{3})')
                ->setFormat('$1 $2-$3')
                ->setLeadingDigitsPattern([
                    '1(?:5[169]|6[3-5]|7[179])|2(?:1[35]|2[34]|3[3-5])',
                    '1(?:5[169]|6(?:3[1-3]|4|5[125])|7(?:1[3-9]|7[0-24-6]|9[2-7]))|2(?:1[35]|2[34]|3[3-5])',
                ])
                ->setNationalPrefixFormattingRule('8 0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2-$3-$4')
                ->setLeadingDigitsPattern(['1(?:[56]|7[467])|2[1-3]'])
                ->setNationalPrefixFormattingRule('8 0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2-$3-$4')
                ->setLeadingDigitsPattern(['[1-4]'])
                ->setNationalPrefixFormattingRule('8 0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[89]'])
                ->setNationalPrefixFormattingRule('8 $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{3,7}|8(?:0[13]|20\d)\d{7}')
            ->setExampleNumber('8011234567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('249\d{6}')
            ->setExampleNumber('249123456')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{3,7}|(?:8(?:0[13]|10|20\d)|902)\d{7}');
    }
}
