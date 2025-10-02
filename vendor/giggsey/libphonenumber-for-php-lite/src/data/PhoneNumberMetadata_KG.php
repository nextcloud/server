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
class PhoneNumberMetadata_KG extends PhoneMetadata
{
    protected const ID = 'KG';
    protected const COUNTRY_CODE = 996;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8\d{9}|[235-9]\d{8}')
            ->setPossibleLengthLocalOnly([5, 6])
            ->setPossibleLength([9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('312(?:58\d|973)\d{3}|(?:2(?:0[0-35]|2\d)|5[0-24-7]\d|600|7(?:[07]\d|55)|88[08]|9(?:12|9[05-9]))\d{6}')
            ->setExampleNumber('700123456')
            ->setPossibleLength([9]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('312(?:5[0-79]\d|9(?:[0-689]\d|7[0-24-9]))\d{3}|(?:3(?:1(?:2[0-46-8]|3[1-9]|47|[56]\d)|2(?:22|3[0-479]|6[0-7])|4(?:22|5[6-9]|6\d)|5(?:22|3[4-7]|59|6\d)|6(?:22|5[35-7]|6\d)|7(?:22|3[468]|4[1-9]|59|[67]\d)|9(?:22|4[1-8]|6\d))|6(?:09|12|2[2-4])\d)\d{5}')
            ->setExampleNumber('312123456')
            ->setPossibleLengthLocalOnly([5, 6])
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['3(?:1[346]|[24-79])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[235-79]|88'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d)(\d{2,3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6,7}')
            ->setExampleNumber('800123456');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
