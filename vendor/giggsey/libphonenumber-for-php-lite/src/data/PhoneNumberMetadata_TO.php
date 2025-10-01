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
class PhoneNumberMetadata_TO extends PhoneMetadata
{
    protected const ID = 'TO';
    protected const COUNTRY_CODE = 676;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:0800|(?:[5-8]\d\d|999)\d)\d{3}|[2-8]\d{4}')
            ->setPossibleLength([5, 7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:5(?:4[0-5]|5[4-6])|6(?:[09]\d|3[02]|8[15-9])|(?:7\d|8[46-9])\d|999)\d{4}')
            ->setExampleNumber('7715123')
            ->setPossibleLength([7]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2\d|3[0-8]|4[0-4]|50|6[09]|7[0-24-69]|8[05])\d{3}')
            ->setExampleNumber('20123')
            ->setPossibleLength([5]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[2-4]|50|6[09]|7[0-24-69]|8[05]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['0'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[5-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0800\d{3}')
            ->setExampleNumber('0800222')
            ->setPossibleLength([7]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('55[0-37-9]\d{4}')
            ->setExampleNumber('5510123')
            ->setPossibleLength([7]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
