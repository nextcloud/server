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
class PhoneNumberMetadata_PL extends PhoneMetadata
{
    protected const ID = 'PL';
    protected const COUNTRY_CODE = 48;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:6|8\d\d)\d{7}|[1-9]\d{6}(?:\d{2})?|[26]\d{5}')
            ->setPossibleLength([6, 7, 8, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2131[89]\d{4}|21(?:1[013-5]|2\d|3[2-9])\d{5}|(?:45|5[0137]|6[069]|7[2389]|88)\d{7}')
            ->setExampleNumber('512345678')
            ->setPossibleLength([9]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70[01346-8]\d{6}')
            ->setExampleNumber('701234567')
            ->setPossibleLength([9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('47\d{7}|(?:1[2-8]|2[2-69]|3[2-4]|4[1-468]|5[24-689]|6[1-3578]|7[14-7]|8[1-79]|9[145])(?:[02-9]\d{6}|1(?:[0-8]\d{5}|9\d{3}(?:\d{2})?))')
            ->setExampleNumber('123456789')
            ->setPossibleLength([7, 9]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{5})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['19'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['11|20|64'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern([
                    '(?:1[2-8]|2[2-69]|3[2-4]|4[1-468]|5[24-689]|6[1-3578]|7[14-7]|8[1-79]|9[145])1',
                    '(?:1[2-8]|2[2-69]|3[2-4]|4[1-468]|5[24-689]|6[1-3578]|7[14-7]|8[1-79]|9[145])19',
                ])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2,3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['64'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['21|39|45|5[0137]|6[0469]|7[02389]|8(?:0[14]|8)'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['1[2-8]|[2-7]|8[1-79]|9[145]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3,4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6,7}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([9, 10]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('801\d{6}')
            ->setExampleNumber('801234567')
            ->setPossibleLength([9]);
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('39\d{7}')
            ->setExampleNumber('391234567')
            ->setPossibleLength([9]);
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('64\d{4,7}')
            ->setExampleNumber('641234567')
            ->setPossibleLength([6, 7, 8, 9]);
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('804\d{6}')
            ->setExampleNumber('804123456')
            ->setPossibleLength([9]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
