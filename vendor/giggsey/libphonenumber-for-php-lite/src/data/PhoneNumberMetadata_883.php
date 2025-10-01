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
class PhoneNumberMetadata_883 extends PhoneMetadata
{
    protected const ID = '001';
    protected const COUNTRY_CODE = 883;

    protected ?string $internationalPrefix = '';
    protected bool $sameMobileAndFixedLinePattern = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[1-4]\d|51)\d{6,10}')
            ->setPossibleLength([8, 9, 10, 11, 12]);
        $this->mobile = PhoneNumberDesc::empty();
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = PhoneNumberDesc::empty();
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{2,8})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[14]|2[24-689]|3[02-689]|51[24-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['510'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['21'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['51[13]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[235]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:00\d\d|10)|(?:370[1-9]|51\d0)\d)\d{7}|51(?:00\d{5}|[24-9]0\d{4,7})|(?:1[0-79]|2[24-689]|3[02-689]|4[0-4])0\d{5,9}')
            ->setExampleNumber('510012345');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
