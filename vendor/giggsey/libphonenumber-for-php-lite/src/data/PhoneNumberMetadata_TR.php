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
class PhoneNumberMetadata_TR extends PhoneMetadata
{
    protected const ID = 'TR';
    protected const COUNTRY_CODE = 90;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4\d{6}|8\d{11,12}|(?:[2-58]\d\d|900)\d{7}')
            ->setPossibleLength([7, 10, 12, 13]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('561(?:011|61\d)\d{4}|5(?:0[15-7]|1[06]|24|[34]\d|5[1-59]|9[46])\d{7}')
            ->setExampleNumber('5012345678')
            ->setPossibleLength([10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:8[89]8|900)\d{7}')
            ->setExampleNumber('9001234567')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:[13][26]|[28][2468]|[45][268]|[67][246])|3(?:[13][28]|[24-6][2468]|[78][02468]|92)|4(?:[16][246]|[23578][2468]|4[26]))\d{7}')
            ->setExampleNumber('2123456789')
            ->setPossibleLength([10]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d)(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['444'])
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['512|8[01589]|90'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['5(?:[0-59]|61)', '5(?:[0-59]|61[06])', '5(?:[0-59]|61[06]1)'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[24][1-8]|3[1-9]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{6,7})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['80'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(true),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:00\d{7}(?:\d{2,3})?|11\d{7})')
            ->setExampleNumber('8001234567')
            ->setPossibleLength([10, 12, 13]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('592(?:21[12]|461)\d{4}')
            ->setExampleNumber('5922121234')
            ->setPossibleLength([10]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('850\d{7}')
            ->setExampleNumber('8500123456')
            ->setPossibleLength([10]);
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('512\d{7}')
            ->setExampleNumber('5123456789')
            ->setPossibleLength([10]);
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('444\d{4}')
            ->setExampleNumber('4441444')
            ->setPossibleLength([7]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:444|811\d{3})\d{4}')
            ->setPossibleLength([7, 10]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['512|8[01589]|90'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['5(?:[0-59]|61)', '5(?:[0-59]|61[06])', '5(?:[0-59]|61[06]1)'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[24][1-8]|3[1-9]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(true),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{6,7})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['80'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(true),
        ];
    }
}
