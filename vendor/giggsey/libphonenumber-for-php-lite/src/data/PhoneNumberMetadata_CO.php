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
class PhoneNumberMetadata_CO extends PhoneMetadata
{
    protected const ID = 'CO';
    protected const COUNTRY_CODE = 57;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0([3579]|4(?:[14]4|56))?';
    protected ?string $internationalPrefix = '00(?:4(?:[14]4|56)|[579])';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:46|60\d\d)\d{6}|(?:1\d|[39])\d{9}')
            ->setPossibleLengthLocalOnly([4, 7])
            ->setPossibleLength([8, 10, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('333301[0-5]\d{3}|3333(?:00|2[5-9]|[3-9]\d)\d{4}|(?:3(?:(?:0[0-5]|1\d|5[01]|70)\d|2(?:[0-3]\d|4[1-9])|3(?:00|3[0-24-9]))|9(?:101|408))\d{6}')
            ->setExampleNumber('3211234567')
            ->setPossibleLength([10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:19(?:0[01]|4[78])|901)\d{7}')
            ->setExampleNumber('19001234567')
            ->setPossibleLength([10, 11]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('601055(?:[0-4]\d|50)\d\d|6010(?:[0-4]\d|5[0-4])\d{4}|(?:46|60(?:[18][1-9]|[24-7][2-9]))\d{6}')
            ->setExampleNumber('6012345678')
            ->setPossibleLengthLocalOnly([4, 7])
            ->setPossibleLength([8, 10]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['46'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['6|90'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setDomesticCarrierCodeFormattingRule('0$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['3[0-357]|9[14]'])
                ->setDomesticCarrierCodeFormattingRule('0$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{7})')
                ->setFormat('$1-$2-$3')
                ->setLeadingDigitsPattern(['1'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1800\d{7}')
            ->setExampleNumber('18001234567')
            ->setPossibleLength([11]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['46'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['6|90'])
                ->setNationalPrefixFormattingRule('($1)')
                ->setDomesticCarrierCodeFormattingRule('0$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['3[0-357]|9[14]'])
                ->setDomesticCarrierCodeFormattingRule('0$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d)(\d{3})(\d{7})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['1']),
        ];
    }
}
