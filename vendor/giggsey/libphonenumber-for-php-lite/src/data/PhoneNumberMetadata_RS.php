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
class PhoneNumberMetadata_RS extends PhoneMetadata
{
    protected const ID = 'RS';
    protected const COUNTRY_CODE = 381;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('38[02-9]\d{6,9}|6\d{7,9}|90\d{4,8}|38\d{5,6}|(?:7\d\d|800)\d{3,9}|(?:[12]\d|3[0-79])\d{5,10}')
            ->setPossibleLengthLocalOnly([4, 5])
            ->setPossibleLength([6, 7, 8, 9, 10, 11, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6(?:[0-689]|7\d)\d{6,7}')
            ->setExampleNumber('601234567')
            ->setPossibleLength([8, 9, 10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:78\d|90[0169])\d{3,7}')
            ->setExampleNumber('90012345')
            ->setPossibleLength([6, 7, 8, 9, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:11[1-9]\d|(?:2[389]|39)(?:0[2-9]|[2-9]\d))\d{3,8}|(?:1[02-9]|2[0-24-7]|3[0-8])[2-9]\d{4,9}')
            ->setExampleNumber('10234567')
            ->setPossibleLengthLocalOnly([4, 5, 6])
            ->setPossibleLength([7, 8, 9, 10, 11, 12]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3,9})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['(?:2[389]|39)0|[7-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{5,10})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[1-36]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{3,9}')
            ->setExampleNumber('80012345');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7[06]\d{4,10}')
            ->setExampleNumber('700123456');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
