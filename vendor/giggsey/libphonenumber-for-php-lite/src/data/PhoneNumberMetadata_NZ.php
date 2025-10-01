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
class PhoneNumberMetadata_NZ extends PhoneMetadata
{
    protected const ID = 'NZ';
    protected const COUNTRY_CODE = 64;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '0(?:0|161)';
    protected ?string $preferredInternationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1289]\d{9}|50\d{5}(?:\d{2,3})?|[27-9]\d{7,8}|(?:[34]\d|6[0-35-9])\d{6}|8\d{4,6}')
            ->setPossibleLength([5, 6, 7, 8, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:[0-27-9]\d|6)\d{6,7}|2(?:1\d|75)\d{5}')
            ->setExampleNumber('211234567')
            ->setPossibleLength([8, 9, 10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1[13-57-9]\d{5}|50(?:0[08]|30|66|77|88))\d{3}|90\d{6,8}')
            ->setExampleNumber('900123456')
            ->setPossibleLength([7, 8, 9, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('240\d{5}|(?:3[2-79]|[49][2-9]|6[235-9]|7[2-57-9])\d{6}')
            ->setExampleNumber('32345678')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,8})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['8[1-79]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2,3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['50[036-8]|8|90', '50(?:[0367]|88)|8|90'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['24|[346]|7[2-57-9]|9[2-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2(?:10|74)|[589]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3,4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1|2[028]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,5})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2(?:[169]|7[0-35-9])|7'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('508\d{6,7}|80\d{6,8}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([8, 9, 10]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70\d{7}')
            ->setExampleNumber('701234567')
            ->setPossibleLength([9]);
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:1[16-9]|22|3\d|4[045]|5[459]|6[235-9]|7[0-3579]|90)\d{2,7}')
            ->setExampleNumber('83012378');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
