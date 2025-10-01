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
class PhoneNumberMetadata_882 extends PhoneMetadata
{
    protected const ID = '001';
    protected const COUNTRY_CODE = 882;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[13]\d{6}(?:\d{2,5})?|[19]\d{7}|(?:[25]\d\d|4)\d{7}(?:\d{2})?')
            ->setPossibleLength([7, 8, 9, 10, 11, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('342\d{4}|(?:337|49)\d{6}|(?:3(?:2|47|7\d{3})|50\d{3})\d{7}')
            ->setExampleNumber('3421234')
            ->setPossibleLength([7, 8, 9, 10, 12]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = PhoneNumberDesc::empty();
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['16|342'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['49'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1[36]|9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['3[23]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['16'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['10|23|3(?:[15]|4[57])|4|51'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['34'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4,5})(\d{5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[1-35]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:3(?:0[0347]|[13][0139]|2[035]|4[013568]|6[0459]|7[06]|8[15-8]|9[0689])\d{4}|6\d{5,10})|(?:345\d|9[89])\d{6}|(?:10|2(?:3|85\d)|3(?:[15]|[69]\d\d)|4[15-8]|51)\d{8}')
            ->setExampleNumber('390123456789');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('348[57]\d{7}')
            ->setExampleNumber('34851234567')
            ->setPossibleLength([11]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
