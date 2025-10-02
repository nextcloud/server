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
class PhoneNumberMetadata_NF extends PhoneMetadata
{
    protected const ID = 'NF';
    protected const COUNTRY_CODE = 672;

    protected ?string $nationalPrefixForParsing = '([0-258]\d{4})$';
    protected ?string $internationalPrefix = '00';
    protected ?string $nationalPrefixTransformRule = '3$1';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[13]\d{5}')
            ->setPossibleLengthLocalOnly([5])
            ->setPossibleLength([6]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:14|3[58])\d{4}')
            ->setExampleNumber('381234')
            ->setPossibleLengthLocalOnly([5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:06|17|28|39)|3[0-2]\d)\d{3}')
            ->setExampleNumber('106609')
            ->setPossibleLengthLocalOnly([5]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['1[0-3]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[13]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
