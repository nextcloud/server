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
class PhoneNumberMetadata_VG extends PhoneMetadata
{
    protected const ID = 'VG';
    protected const COUNTRY_CODE = 1;
    protected const LEADING_DIGITS = '284';
    protected const NATIONAL_PREFIX = '1';

    protected ?string $nationalPrefixForParsing = '([2-578]\d{6})$|1';
    protected ?string $internationalPrefix = '011';
    protected ?string $nationalPrefixTransformRule = '284$1';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:284|[58]\d\d|900)\d{7}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('284(?:245|3(?:0[0-3]|4[0-7]|68|9[34])|4(?:4[0-6]|68|9[69])|5(?:4[0-7]|68|9[69]))\d{4}')
            ->setExampleNumber('2843001234')
            ->setPossibleLengthLocalOnly([7]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900[2-9]\d{6}')
            ->setExampleNumber('9002345678');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('284(?:229|4(?:22|9[45])|774|8(?:52|6[459]))\d{4}')
            ->setExampleNumber('2842291234')
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
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
