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
class PhoneNumberMetadata_MQ extends PhoneMetadata
{
    protected const ID = 'MQ';
    protected const COUNTRY_CODE = 596;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:596\d|7091)\d{5}|(?:69|[89]\d)\d{7}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:69[67]\d\d|7091[0-3])\d{4}')
            ->setExampleNumber('696201234');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8[129]\d{7}')
            ->setExampleNumber('810123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:596(?:[03-7]\d|1[05]|2[7-9]|8[0-39]|9[04-9])|80[6-9]\d\d|9(?:477[6-9]|767[4589]))\d{4}')
            ->setExampleNumber('596301234');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[5-79]|8(?:0[6-9]|[36])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[0-5]\d{6}')
            ->setExampleNumber('800012345');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:397[0-3]|477[0-5]|76(?:6\d|7[0-367]))\d{4}')
            ->setExampleNumber('976612345');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
