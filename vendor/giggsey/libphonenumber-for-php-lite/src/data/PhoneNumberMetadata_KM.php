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
class PhoneNumberMetadata_KM extends PhoneMetadata
{
    protected const ID = 'KM';
    protected const COUNTRY_CODE = 269;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[3478]\d{6}')
            ->setPossibleLengthLocalOnly([4])
            ->setPossibleLength([7]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[34]\d{6}')
            ->setExampleNumber('3212345');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8\d{6}')
            ->setExampleNumber('8001234');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7[4-7]\d{5}')
            ->setExampleNumber('7712345')
            ->setPossibleLengthLocalOnly([4]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[3478]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
