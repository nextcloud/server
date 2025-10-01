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
class PhoneNumberMetadata_PE extends PhoneMetadata
{
    protected const ID = 'PE';
    protected const COUNTRY_CODE = 51;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00|19(?:1[124]|77|90)00';
    protected ?string $preferredInternationalPrefix = '00';
    protected ?string $preferredExtnPrefix = ' Anexo ';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[14-8]|9\d)\d{7}')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('9\d{8}')
            ->setExampleNumber('912345678')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('805\d{5}')
            ->setExampleNumber('80512345')
            ->setPossibleLength([8]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:(?:(?:4[34]|5[14])[0-8]|687)\d|7(?:173|(?:3[0-8]|55)\d)|8(?:10[05689]|6(?:0[06-9]|1[6-9]|29)|7(?:0[0569]|[56]0)))\d{4}|(?:1[0-8]|4[12]|5[236]|6[1-7]|7[246]|8[2-4])\d{6}')
            ->setExampleNumber('11234567')
            ->setPossibleLengthLocalOnly([6, 7])
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['80'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[4-8]'])
                ->setNationalPrefixFormattingRule('(0$1)')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{5}')
            ->setExampleNumber('80012345')
            ->setPossibleLength([8]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('801\d{5}')
            ->setExampleNumber('80112345')
            ->setPossibleLength([8]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[24]\d{5}')
            ->setExampleNumber('80212345')
            ->setPossibleLength([8]);
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
