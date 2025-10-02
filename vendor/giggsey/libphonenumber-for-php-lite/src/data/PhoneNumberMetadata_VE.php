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
class PhoneNumberMetadata_VE extends PhoneMetadata
{
    protected const ID = 'VE';
    protected const COUNTRY_CODE = 58;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[68]00\d{7}|(?:[24]\d|[59]0)\d{8}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4(?:1[24-8]|2[246])\d{7}')
            ->setExampleNumber('4121234567');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90[01]\d{7}')
            ->setExampleNumber('9001234567');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:12|3[457-9]|[467]\d|[58][1-9]|9[1-6])|[4-6]00)\d{7}')
            ->setExampleNumber('2121234567')
            ->setPossibleLengthLocalOnly([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{7})')
                ->setFormat('$1-$2')
                ->setLeadingDigitsPattern(['[24-689]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setDomesticCarrierCodeFormattingRule('$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{7}')
            ->setExampleNumber('8001234567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('501\d{7}')
            ->setExampleNumber('5010123456')
            ->setPossibleLengthLocalOnly([7]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
