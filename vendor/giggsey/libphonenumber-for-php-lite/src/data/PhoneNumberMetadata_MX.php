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
class PhoneNumberMetadata_MX extends PhoneMetadata
{
    protected const ID = 'MX';
    protected const COUNTRY_CODE = 52;

    protected ?string $internationalPrefix = '0[09]';
    protected ?string $preferredInternationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-9]\d{9}')
            ->setPossibleLengthLocalOnly([7, 8])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:2\d|3[1-35-8]|4[13-9]|7[1-689]|8[1-578]|9[467])|3(?:1[1-79]|[2458][1-9]|3\d|7[1-8]|9[1-5])|4(?:1[1-57-9]|[267][1-9]|3[1-8]|[45]\d|8[1-35-9]|9[2-689])|5(?:[56]\d|88|9[1-79])|6(?:1[2-68]|[2-4][1-9]|5[1-36-9]|6[0-57-9]|7[1-7]|8[67]|9[4-8])|7(?:[1346][1-9]|[27]\d|5[13-9]|8[1-69]|9[17])|8(?:1\d|2[13-689]|3[1-6]|4[124-6]|6[1246-9]|7[0-378]|9[12479])|9(?:1[346-9]|2[1-4]|3[2-46-8]|5[1348]|[69]\d|7[12]|8[1-8]))\d{7}')
            ->setExampleNumber('2221234567')
            ->setPossibleLengthLocalOnly([7, 8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900\d{7}')
            ->setExampleNumber('9001234567');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:0[01]|2\d|3[1-35-8]|4[13-9]|7[1-689]|8[1-578]|9[467])|3(?:1[1-79]|[2458][1-9]|3\d|7[1-8]|9[1-5])|4(?:1[1-57-9]|[267][1-9]|3[1-8]|[45]\d|8[1-35-9]|9[2-689])|5(?:[56]\d|88|9[1-79])|6(?:1[2-68]|[2-4][1-9]|5[1-36-9]|6[0-57-9]|7[1-7]|8[67]|9[4-8])|7(?:[1346][1-9]|[27]\d|5[13-9]|8[1-69]|9[17])|8(?:1\d|2[13-689]|3[1-6]|4[124-6]|6[1246-9]|7[0-378]|9[12479])|9(?:1[346-9]|2[1-4]|3[2-46-8]|5[1348]|[69]\d|7[12]|8[1-8]))\d{7}')
            ->setExampleNumber('2001234567')
            ->setPossibleLengthLocalOnly([7, 8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{5})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['53'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['33|5[56]|81'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:00|88)\d{7}')
            ->setExampleNumber('8001234567');
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('300\d{7}')
            ->setExampleNumber('3001234567');
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('500\d{7}')
            ->setExampleNumber('5001234567');
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
        $this->intlNumberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['33|5[56]|81'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[2-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
    }
}
