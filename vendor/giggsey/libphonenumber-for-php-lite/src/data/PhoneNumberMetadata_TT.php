<?php

/**
 * libphonenumber-for-php-lite data file
 * This file has been @generated from libphonenumber data
 * Do not modify!
 * @internal
 */

declare(strict_types=1);

namespace libphonenumber\data;

use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumberDesc;

/**
 * @internal
 */
class PhoneNumberMetadata_TT extends PhoneMetadata
{
    protected const ID = 'TT';
    protected const COUNTRY_CODE = 1;
    protected const LEADING_DIGITS = '868';
    protected const NATIONAL_PREFIX = '1';

    protected ?string $nationalPrefixForParsing = '([2-46-8]\d{6})$|1';
    protected ?string $internationalPrefix = '011';
    protected ?string $nationalPrefixTransformRule = '868$1';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[58]\d\d|900)\d{7}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('868(?:(?:2[5-9]|3\d)\d|4(?:3[0-6]|[6-9]\d)|6(?:20|78|8\d)|7(?:0[1-9]|1[02-9]|[2-9]\d))\d{4}')
            ->setExampleNumber('8682911234')
            ->setPossibleLengthLocalOnly([7]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900[2-9]\d{6}')
            ->setExampleNumber('9002345678');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('868(?:2(?:01|1[5-9]|[23]\d|4[0-2])|6(?:0[7-9]|1[02-8]|2[1-9]|[3-69]\d|7[0-79])|82[124])\d{4}')
            ->setExampleNumber('8682211234')
            ->setPossibleLengthLocalOnly([7]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:00|33|44|55|66|77|88)[2-9]\d{6}')
            ->setExampleNumber('8002345678');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('52(?:3(?:[2-46-9][02-9]\d|5(?:[02-46-9]\d|5[0-46-9]))|4(?:[2-478][02-9]\d|5(?:[034]\d|2[024-9]|5[0-46-9])|6(?:0[1-9]|[2-9]\d)|9(?:[05-9]\d|2[0-5]|49)))\d{4}|52[34][2-9]1[02-9]\d{4}|5(?:00|2[125-9]|33|44|66|77|88)[2-9]\d{6}')
            ->setExampleNumber('5002345678');
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('868619\d{4}')
            ->setExampleNumber('8686191234')
            ->setPossibleLengthLocalOnly([7]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
