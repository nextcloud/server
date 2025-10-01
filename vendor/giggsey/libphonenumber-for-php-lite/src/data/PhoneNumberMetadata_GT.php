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
class PhoneNumberMetadata_GT extends PhoneMetadata
{
    protected const ID = 'GT';
    protected const COUNTRY_CODE = 502;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80\d{6}|(?:1\d{3}|[2-7])\d{7}')
            ->setPossibleLength([8, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[3-5]\d\d|80[0-4])\d{5}')
            ->setExampleNumber('51234567')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('19\d{9}')
            ->setExampleNumber('19001112222')
            ->setPossibleLength([11]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[267][2-9]\d{6}')
            ->setExampleNumber('22456789')
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[2-8]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('18[01]\d{8}')
            ->setExampleNumber('18001112222')
            ->setPossibleLength([11]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
