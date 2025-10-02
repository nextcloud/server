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
class PhoneNumberMetadata_DO extends PhoneMetadata
{
    protected const ID = 'DO';
    protected const COUNTRY_CODE = 1;
    protected const LEADING_DIGITS = '8001|8[024]9';
    protected const NATIONAL_PREFIX = '1';

    protected ?string $nationalPrefixForParsing = '1';
    protected ?string $internationalPrefix = '011';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:[58]\d\d|900)\d{7}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8[024]9[2-9]\d{6}')
            ->setExampleNumber('8092345678')
            ->setPossibleLengthLocalOnly([7]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900[2-9]\d{6}')
            ->setExampleNumber('9002123456');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:[04]9[2-9]\d\d|29(?:2(?:[0-59]\d|6[04-9]|7[0-27]|8[0237-9])|3(?:[0-35-9]\d|4[7-9])|[45]\d\d|6(?:[0-27-9]\d|[3-5][1-9]|6[0135-8])|7(?:0[013-9]|[1-37]\d|4[1-35689]|5[1-4689]|6[1-57-9]|8[1-79]|9[1-8])|8(?:0[146-9]|1[0-48]|[248]\d|3[1-79]|5[01589]|6[013-68]|7[124-8]|9[0-8])|9(?:[0-24]\d|3[02-46-9]|5[0-79]|60|7[0169]|8[57-9]|9[02-9])))\d{4}')
            ->setExampleNumber('8092345678')
            ->setPossibleLengthLocalOnly([7]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:00(?:14|[2-9]\d)|(?:33|44|55|66|77|88)[2-9]\d)\d{5}')
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
