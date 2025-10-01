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
class PhoneNumberMetadata_CL extends PhoneMetadata
{
    protected const ID = 'CL';
    protected const COUNTRY_CODE = 56;

    protected ?string $internationalPrefix = '(?:0|1(?:1[0-69]|2[02-5]|5[13-58]|69|7[0167]|8[018]))0';
    protected bool $mobileNumberPortableRegion = true;
    protected bool $sameMobileAndFixedLinePattern = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('12300\d{6}|6\d{9,10}|[2-9]\d{8}')
            ->setPossibleLength([9, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:1982[0-6]|3314[05-9])\d{3}|(?:2(?:1(?:160|962)|3(?:2\d\d|3(?:[03467]\d|1[0-35-9]|2[1-9]|5[0-24-9]|8[0-3])|600)|646[59])|80[1-9]\d\d|9(?:3(?:[0-57-9]\d\d|6(?:0[02-9]|[1-9]\d))|6(?:[0-8]\d\d|9(?:[02-79]\d|1[05-9]))|7[1-9]\d\d|9(?:[03-9]\d\d|1(?:[0235-9]\d|4[0-24-9])|2(?:[0-79]\d|8[0-46-9]))))\d{4}|(?:22|3[2-5]|[47][1-35]|5[1-3578]|6[13-57]|8[1-9]|9[2458])\d{7}')
            ->setExampleNumber('221234567')
            ->setPossibleLength([9]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:1982[0-6]|3314[05-9])\d{3}|(?:2(?:1(?:160|962)|3(?:2\d\d|3(?:[03467]\d|1[0-35-9]|2[1-9]|5[0-24-9]|8[0-3])|600)|646[59])|80[1-9]\d\d|9(?:3(?:[0-57-9]\d\d|6(?:0[02-9]|[1-9]\d))|6(?:[0-8]\d\d|9(?:[02-79]\d|1[05-9]))|7[1-9]\d\d|9(?:[03-9]\d\d|1(?:[0235-9]\d|4[0-24-9])|2(?:[0-79]\d|8[0-46-9]))))\d{4}|(?:22|3[2-5]|[47][1-35]|5[1-3578]|6[13-57]|8[1-9]|9[2458])\d{7}')
            ->setExampleNumber('221234567')
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['1(?:[03-589]|21)|[29]0|78'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['219', '2196'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['44'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2[1-36]'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9[2-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['3[2-5]|[47]|5[1-3578]|6[13-57]|8(?:0[1-9]|[1-9])'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['60|8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['60'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:123|8)00\d{6}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([9, 11]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('600\d{7,8}')
            ->setExampleNumber('6001234567')
            ->setPossibleLength([10, 11]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('44\d{7}')
            ->setExampleNumber('441234567')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('600\d{7,8}')
            ->setPossibleLength([10, 11]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['219', '2196'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['44'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2[1-36]'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9[2-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['3[2-5]|[47]|5[1-3578]|6[13-57]|8(?:0[1-9]|[1-9])'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['60|8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['60'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
