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
class PhoneNumberMetadata_MT extends PhoneMetadata
{
    protected const ID = 'MT';
    protected const COUNTRY_CODE = 356;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3550\d{4}|(?:[2579]\d\d|800)\d{5}')
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:7(?:210|[79]\d\d)|9(?:[29]\d\d|69[67]|8(?:1[1-3]|89|97)))\d{4}')
            ->setExampleNumber('96961234');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5(?:0(?:0(?:37|43)|(?:6\d|70|9[0168])\d)|[12]\d0[1-5])\d{3}')
            ->setExampleNumber('50037123');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('20(?:3[1-4]|6[059])\d{4}|2(?:0[19]|[1-357]\d|60)\d{5}')
            ->setExampleNumber('21001234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[2357-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800(?:02|[3467]\d)\d{3}')
            ->setExampleNumber('80071234');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3550\d{4}')
            ->setExampleNumber('35501234');
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7117\d{4}')
            ->setExampleNumber('71171234');
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('501\d{5}')
            ->setExampleNumber('50112345');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
