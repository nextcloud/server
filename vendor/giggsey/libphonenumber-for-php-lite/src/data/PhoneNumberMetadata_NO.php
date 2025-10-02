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
class PhoneNumberMetadata_NO extends PhoneMetadata
{
    protected const ID = 'NO';
    protected const COUNTRY_CODE = 47;
    protected const LEADING_DIGITS = '[02-689]|7[0-8]';

    protected ?string $internationalPrefix = '00';
    protected bool $mainCountryForCode = true;
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:0|[2-9]\d{3})\d{4}')
            ->setPossibleLength([5, 8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:4[015-8]|9\d)\d{6}')
            ->setExampleNumber('40612345')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('82[09]\d{5}')
            ->setExampleNumber('82012345')
            ->setPossibleLength([8]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2[1-4]|3[1-3578]|5[1-35-7]|6[1-4679]|7[0-8])\d{6}')
            ->setExampleNumber('21234567')
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[2-79]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[01]\d{5}')
            ->setExampleNumber('80012345')
            ->setPossibleLength([8]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('810(?:0[0-6]|[2-8]\d)\d{3}')
            ->setExampleNumber('81021234')
            ->setPossibleLength([8]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('880\d{5}')
            ->setExampleNumber('88012345')
            ->setPossibleLength([8]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('85[0-5]\d{5}')
            ->setExampleNumber('85012345')
            ->setPossibleLength([8]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:0[235-9]|81(?:0(?:0[7-9]|1\d)|5\d\d))\d{3}')
            ->setExampleNumber('02000');
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('81[23]\d{5}')
            ->setExampleNumber('81212345')
            ->setPossibleLength([8]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
