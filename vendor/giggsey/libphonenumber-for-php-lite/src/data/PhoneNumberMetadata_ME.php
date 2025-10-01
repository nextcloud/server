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
class PhoneNumberMetadata_ME extends PhoneMetadata
{
    protected const ID = 'ME';
    protected const COUNTRY_CODE = 382;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:20|[3-79]\d)\d{6}|80\d{6,7}')
            ->setPossibleLengthLocalOnly([6])
            ->setPossibleLength([8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('6(?:[07-9]\d|3[024]|6[0-25])\d{5}')
            ->setExampleNumber('67622901')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9(?:4[1568]|5[178])\d{5}')
            ->setExampleNumber('94515151')
            ->setPossibleLength([8]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:20[2-8]|3(?:[0-2][2-7]|3[24-7])|4(?:0[2-467]|1[2467])|5(?:0[2467]|1[24-7]|2[2-467]))\d{5}')
            ->setExampleNumber('30234567')
            ->setPossibleLengthLocalOnly([6])
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80(?:[0-2578]|9\d)\d{5}')
            ->setExampleNumber('80080002');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('78[1-49]\d{5}')
            ->setExampleNumber('78108780')
            ->setPossibleLength([8]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('77[1-9]\d{5}')
            ->setExampleNumber('77273012')
            ->setPossibleLength([8]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
