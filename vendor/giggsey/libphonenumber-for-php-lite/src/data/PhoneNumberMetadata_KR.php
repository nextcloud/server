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
class PhoneNumberMetadata_KR extends PhoneMetadata
{
    protected const ID = 'KR';
    protected const COUNTRY_CODE = 82;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0(8(?:[1-46-8]|5\d\d))?';
    protected ?string $internationalPrefix = '00(?:[125689]|3(?:[46]5|91)|7(?:00|27|3|55|6[126]))';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('00[1-9]\d{8,11}|(?:[12]|5\d{3})\d{7}|[13-6]\d{9}|(?:[1-6]\d|80)\d{7}|[3-6]\d{4,5}|(?:00|7)0\d{8}')
            ->setPossibleLengthLocalOnly([3, 4, 7])
            ->setPossibleLength([5, 6, 8, 9, 10, 11, 12, 13, 14]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:05(?:[0-8]\d|9[0-6])|22[13]\d)\d{4,5}|1(?:0[0-46-9]|[16-9]\d|2[013-9])\d{6,7}')
            ->setExampleNumber('1020000000')
            ->setPossibleLength([9, 10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('60[2-9]\d{6}')
            ->setExampleNumber('602345678')
            ->setPossibleLength([9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2|3[1-3]|[46][1-4]|5[1-5])[1-9]\d{6,7}|(?:3[1-3]|[46][1-4]|5[1-5])1\d{2,3}')
            ->setExampleNumber('22123456')
            ->setPossibleLengthLocalOnly([3, 4, 7])
            ->setPossibleLength([5, 6, 8, 9, 10]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{5})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['1[016-9]1', '1[016-9]11', '1[016-9]114'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['(?:3[1-3]|[46][1-4]|5[1-5])1'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3,4})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['2'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['[36]0|8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['[1346]|5[1-5]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['[57]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['003', '0030'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['5'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['0'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['0'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('00(?:308\d{6,7}|798\d{7,9})|(?:00368|[38]0)\d{7}')
            ->setExampleNumber('801234567')
            ->setPossibleLength([9, 11, 12, 13, 14]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('50\d{8,9}')
            ->setExampleNumber('5012345678')
            ->setPossibleLength([10, 11]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70\d{8}')
            ->setExampleNumber('7012345678')
            ->setPossibleLength([10]);
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('15\d{7,8}')
            ->setExampleNumber('1523456789')
            ->setPossibleLength([9, 10]);
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:5(?:22|33|44|66|77|88|99)|6(?:[07]0|44|6[0168]|88)|8(?:00|33|55|77|99))\d{4}')
            ->setExampleNumber('15441234')
            ->setPossibleLength([8]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('00(?:3(?:08\d{6,7}|68\d{7})|798\d{7,9})')
            ->setPossibleLength([11, 12, 13, 14]);
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['(?:3[1-3]|[46][1-4]|5[1-5])1'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3,4})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['2'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['[36]0|8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['[1346]|5[1-5]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['[57]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5})(\d{4})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['5'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('0$CC-$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
