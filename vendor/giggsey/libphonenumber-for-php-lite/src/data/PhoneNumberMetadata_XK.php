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
class PhoneNumberMetadata_XK extends PhoneMetadata
{
    protected const ID = 'XK';
    protected const COUNTRY_CODE = 383;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2\d{7,8}|3\d{7,11}|(?:4\d\d|[89]00)\d{5}')
            ->setPossibleLength([8, 9, 10, 11, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4[3-9]\d{6}')
            ->setExampleNumber('43201234')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900\d{5}')
            ->setExampleNumber('90001234')
            ->setPossibleLength([8]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('38\d{6,10}|(?:2[89]|39)(?:0\d{5,6}|[1-9]\d{5})')
            ->setExampleNumber('28012345');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[89]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-4]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['2|39'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{7,10})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['3'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{5}')
            ->setExampleNumber('80001234')
            ->setPossibleLength([8]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
