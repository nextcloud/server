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
class PhoneNumberMetadata_CU extends PhoneMetadata
{
    protected const ID = 'CU';
    protected const COUNTRY_CODE = 53;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '119';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[2-7]|8\d\d)\d{7}|[2-47]\d{6}|[34]\d{5}')
            ->setPossibleLengthLocalOnly([4, 5])
            ->setPossibleLength([6, 7, 8, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:5\d|6[2-4])\d{6}')
            ->setExampleNumber('51234567')
            ->setPossibleLength([8]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3[23]|4[89])\d{4,6}|(?:31|4[36]|8(?:0[25]|78)\d)\d{6}|(?:2[1-4]|4[1257]|7\d)\d{5,6}')
            ->setExampleNumber('71234567')
            ->setPossibleLengthLocalOnly([4, 5]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['2[1-4]|[34]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{6,7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[56]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{7}')
            ->setExampleNumber('8001234567')
            ->setPossibleLength([10]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('807\d{7}')
            ->setExampleNumber('8071234567')
            ->setPossibleLength([10]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
