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
class PhoneNumberMetadata_SK extends PhoneMetadata
{
    protected const ID = 'SK';
    protected const COUNTRY_CODE = 421;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-689]\d{8}|[2-59]\d{6}|[2-5]\d{5}')
            ->setPossibleLength([6, 7, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('909[1-9]\d{5}|9(?:0[1-8]|1[0-24-9]|4[03-57-9]|5\d)\d{6}')
            ->setExampleNumber('912123456')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:00|[78]\d)\d{6}')
            ->setExampleNumber('900123456')
            ->setPossibleLength([9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:16|[2-9]\d{3})|(?:(?:[3-5][1-8]\d|819)\d|601[1-5])\d)\d{4}|(?:2|[3-5][1-8])1[67]\d{3}|[3-5][1-8]16\d\d')
            ->setExampleNumber('221234567');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{2})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['21'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2,3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[3-5][1-8]1', '[3-5][1-8]1[67]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['909', '9090'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{3})(\d{2})')
                ->setFormat('$1/$2 $3 $4')
                ->setLeadingDigitsPattern(['2'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[689]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1/$2 $3 $4')
                ->setLeadingDigitsPattern(['[3-5]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([9]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8[5-9]\d{7}')
            ->setExampleNumber('850123456')
            ->setPossibleLength([9]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6(?:02|5[0-4]|9[0-6])\d{6}')
            ->setExampleNumber('690123456')
            ->setPossibleLength([9]);
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9090\d{3}')
            ->setExampleNumber('9090123')
            ->setPossibleLength([7]);
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('96\d{7}')
            ->setExampleNumber('961234567')
            ->setPossibleLength([9]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9090\d{3}|(?:602|8(?:00|[5-9]\d)|9(?:00|[78]\d))\d{6}')
            ->setPossibleLength([7, 9]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{2})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['21'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2,3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[3-5][1-8]1', '[3-5][1-8]1[67]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{3})(\d{2})')
                ->setFormat('$1/$2 $3 $4')
                ->setLeadingDigitsPattern(['2'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[689]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1/$2 $3 $4')
                ->setLeadingDigitsPattern(['[3-5]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
