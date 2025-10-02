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
class PhoneNumberMetadata_JM extends PhoneMetadata
{
    protected const ID = 'JM';
    protected const COUNTRY_CODE = 1;
    protected const LEADING_DIGITS = '658|876';
    protected const NATIONAL_PREFIX = '1';

    protected ?string $nationalPrefixForParsing = '1';
    protected ?string $internationalPrefix = '011';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[58]\d\d|658|900)\d{7}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:658295|876(?:2(?:0[1-9]|[13-9]\d|2[013-9])|[348]\d\d|5(?:0[1-9]|[1-9]\d)|6(?:4[89]|6[67])|7(?:0[07]|7\d|8[1-47-9]|9[0-36-9])|9(?:[01]9|9[0579])))\d{4}')
            ->setExampleNumber('8762101234')
            ->setPossibleLengthLocalOnly([7]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900[2-9]\d{6}')
            ->setExampleNumber('9002123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8766060\d{3}|(?:658(?:2(?:[0-8]\d|9[0-46-9])|[3-9]\d\d)|876(?:52[35]|6(?:0[1-3579]|1[0235-9]|[23]\d|40|5[06]|6[2-589]|7[0-25-9]|8[04]|9[4-9])|7(?:0[2-689]|[1-6]\d|8[056]|9[45])|9(?:0[1-8]|1[02378]|[2-8]\d|9[2-468])))\d{4}')
            ->setExampleNumber('8765230123')
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
