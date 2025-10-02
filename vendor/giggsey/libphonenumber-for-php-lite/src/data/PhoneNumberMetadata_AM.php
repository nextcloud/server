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
class PhoneNumberMetadata_AM extends PhoneMetadata
{
    protected const ID = 'AM';
    protected const COUNTRY_CODE = 374;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[1-489]\d|55|60|77)\d{6}')
            ->setPossibleLengthLocalOnly([5, 6])
            ->setPossibleLength([8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:33|4[1349]|55|77|88|9[13-9])\d{6}')
            ->setExampleNumber('77123456');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('90[016]\d{5}')
            ->setExampleNumber('90012345');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:(?:1[0-25]|47)\d|2(?:2[2-46]|3[1-8]|4[2-69]|5[2-7]|6[1-9]|8[1-7])|3[12]2)\d{5}')
            ->setExampleNumber('10123456')
            ->setPossibleLengthLocalOnly([5, 6]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[89]0'])
                ->setNationalPrefixFormattingRule('0 $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['2|3[12]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['1|47'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[3-9]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{5}')
            ->setExampleNumber('80012345');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[1-4]\d{5}')
            ->setExampleNumber('80112345');
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('60(?:2[78]|3[5-9]|4[02-9]|5[0-46-9]|[6-8]\d|9[0-2])\d{4}')
            ->setExampleNumber('60271234');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
