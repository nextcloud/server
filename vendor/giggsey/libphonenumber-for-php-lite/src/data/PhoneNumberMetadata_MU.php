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
class PhoneNumberMetadata_MU extends PhoneMetadata
{
    protected const ID = 'MU';
    protected const COUNTRY_CODE = 230;

    protected ?string $internationalPrefix = '0(?:0|[24-7]0|3[03])';
    protected ?string $preferredInternationalPrefix = '020';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[57]|8\d\d)\d{7}|[2-468]\d{6}')
            ->setPossibleLength([7, 8, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5(?:4(?:2[1-389]|7[1-9])|87[15-8])\d{4}|(?:5(?:2[5-9]|4[3-689]|[57]\d|8[0-689]|9[0-8])|7(?:0[0-6]|3[013]))\d{5}')
            ->setExampleNumber('52512345')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('30\d{5}')
            ->setExampleNumber('3012345')
            ->setPossibleLength([7]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:[0346-8]\d|1[0-8])|4(?:[013568]\d|2[4-8]|71|90)|54(?:[3-5]\d|71)|6\d\d|8(?:14|3[129]))\d{4}')
            ->setExampleNumber('54480123')
            ->setPossibleLength([7, 8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[2-46]|8[013]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[57]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('802\d{7}|80[0-2]\d{4}')
            ->setExampleNumber('8001234')
            ->setPossibleLength([7, 10]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3(?:20|9\d)\d{4}')
            ->setExampleNumber('3201234')
            ->setPossibleLength([7]);
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('219\d{4}')
            ->setExampleNumber('2190123')
            ->setPossibleLength([7]);
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
