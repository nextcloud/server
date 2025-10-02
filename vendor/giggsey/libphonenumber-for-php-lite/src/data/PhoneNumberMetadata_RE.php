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
class PhoneNumberMetadata_RE extends PhoneMetadata
{
    protected const ID = 'RE';
    protected const COUNTRY_CODE = 262;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mainCountryForCode = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('709\d{6}|(?:26|[689]\d)\d{7}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:69(?:2\d\d|3(?:[06][0-6]|1[0-3]|2[0-2]|3[0-39]|4\d|5[0-5]|7[0-37]|8[0-8]|9[0-479]))|7092[0-3])\d{4}')
            ->setExampleNumber('692123456');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('89[1-37-9]\d{6}')
            ->setExampleNumber('891123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('26(?:2\d\d|3(?:0\d|1[0-6]))\d{4}')
            ->setExampleNumber('262161234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[26-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{7}')
            ->setExampleNumber('801234567');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:1[019]|2[0156]|84|90)\d{6}')
            ->setExampleNumber('810123456');
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:399[0-3]|479[0-6]|76(?:2[278]|3[0-37]))\d{4}')
            ->setExampleNumber('939901234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
