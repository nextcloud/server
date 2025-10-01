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
class PhoneNumberMetadata_YE extends PhoneMetadata
{
    protected const ID = 'YE';
    protected const COUNTRY_CODE = 967;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1|7\d)\d{7}|[1-7]\d{6}')
            ->setPossibleLengthLocalOnly([6])
            ->setPossibleLength([7, 8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7[01378]\d{7}')
            ->setExampleNumber('712345678')
            ->setPossibleLength([9]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('78[0-7]\d{4}|17\d{6}|(?:[12][2-68]|3[2358]|4[2-58]|5[2-6]|6[3-58]|7[24-6])\d{5}')
            ->setExampleNumber('1234567')
            ->setPossibleLengthLocalOnly([6])
            ->setPossibleLength([7, 8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[1-6]|7(?:[24-6]|8[0-7])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
