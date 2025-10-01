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
class PhoneNumberMetadata_TN extends PhoneMetadata
{
    protected const ID = 'TN';
    protected const COUNTRY_CODE = 216;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-57-9]\d{7}')
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3(?:001|[12]40)\d{4}|(?:(?:[259]\d|4[0-8])\d|3(?:1[1-35]|6[0-4]|91))\d{5}')
            ->setExampleNumber('20123456');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('88\d{6}')
            ->setExampleNumber('88123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('81200\d{3}|(?:3[0-2]|7\d)\d{6}')
            ->setExampleNumber('30010123');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-57-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8010\d{4}')
            ->setExampleNumber('80101234');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8[12]10\d{4}')
            ->setExampleNumber('81101234');
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
