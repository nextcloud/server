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
class PhoneNumberMetadata_LC extends PhoneMetadata
{
    protected const ID = 'LC';
    protected const COUNTRY_CODE = 1;
    protected const LEADING_DIGITS = '758';
    protected const NATIONAL_PREFIX = '1';

    protected ?string $nationalPrefixForParsing = '([2-8]\d{6})$|1';
    protected ?string $internationalPrefix = '011';
    protected ?string $nationalPrefixTransformRule = '758$1';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[58]\d\d|758|900)\d{7}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('758(?:28[4-7]|384|4(?:6[01]|8[4-9])|5(?:1[89]|20|84)|7(?:1[2-9]|2\d|3[0-3])|812)\d{4}')
            ->setExampleNumber('7582845678')
            ->setPossibleLengthLocalOnly([7]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900[2-9]\d{6}')
            ->setExampleNumber('9002123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('758(?:234|4(?:30|5\d|6[2-9]|8[0-2])|57[0-2]|(?:63|75)8)\d{4}')
            ->setExampleNumber('7584305678')
            ->setPossibleLengthLocalOnly([7]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:00|33|44|55|66|77|88)[2-9]\d{6}')
            ->setExampleNumber('8002123456');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('52(?:3(?:[2-46-9][02-9]\d|5(?:[02-46-9]\d|5[0-46-9]))|4(?:[2-478][02-9]\d|5(?:[034]\d|2[024-9]|5[0-46-9])|6(?:0[1-9]|[2-9]\d)|9(?:[05-9]\d|2[0-5]|49)))\d{4}|52[34][2-9]1[02-9]\d{4}|5(?:00|2[125-9]|33|44|66|77|88)[2-9]\d{6}')
            ->setExampleNumber('5002345678');
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
