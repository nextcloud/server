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
class PhoneNumberMetadata_ZA extends PhoneMetadata
{
    protected const ID = 'ZA';
    protected const COUNTRY_CODE = 27;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-79]\d{8}|8\d{4,9}')
            ->setPossibleLength([5, 6, 7, 8, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:3492[0-25]|4495[0235]|549(?:20|5[01]))|4[34]492[01])\d{3}|8[1-4]\d{3,7}|(?:2[27]|47|54)4950\d{3}|(?:1(?:049[2-4]|9[12]\d\d)|(?:50[0-2]|6\d\d|7(?:[0-46-9]\d|5[0-4]))\d\d|8(?:5\d{3}|7(?:08[67]|158|28[5-9]|310)))\d{4}|(?:1[6-8]|28|3[2-69]|4[025689]|5[36-8])4920\d{3}|(?:12|[2-5]1)492\d{4}')
            ->setExampleNumber('711234567')
            ->setPossibleLength([5, 6, 7, 8, 9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:86[2-9]|9[0-2]\d)\d{6}')
            ->setExampleNumber('862345678')
            ->setPossibleLength([9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:0330|4302)|52087)0\d{3}|(?:1[0-8]|2[1-378]|3[1-69]|4\d|5[1346-8])\d{7}')
            ->setExampleNumber('101234567')
            ->setPossibleLength([9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['8[1-4]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2,3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8[1-4]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['860'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[1-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{7}')
            ->setExampleNumber('801234567')
            ->setPossibleLength([9]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('860\d{6}')
            ->setExampleNumber('860123456')
            ->setPossibleLength([9]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('87(?:08[0-589]|15[0-79]|28[0-4]|31[1-9])\d{4}|87(?:[02][0-79]|1[0-46-9]|3[02-9]|[4-9]\d)\d{5}')
            ->setExampleNumber('871234567')
            ->setPossibleLength([9]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('861\d{6,7}')
            ->setExampleNumber('861123456')
            ->setPossibleLength([9, 10]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
