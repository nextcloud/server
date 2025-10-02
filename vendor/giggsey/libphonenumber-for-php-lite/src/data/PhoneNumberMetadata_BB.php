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
class PhoneNumberMetadata_BB extends PhoneMetadata
{
    protected const ID = 'BB';
    protected const COUNTRY_CODE = 1;
    protected const LEADING_DIGITS = '246';
    protected const NATIONAL_PREFIX = '1';

    protected ?string $nationalPrefixForParsing = '([2-9]\d{6})$|1';
    protected ?string $internationalPrefix = '011';
    protected ?string $nationalPrefixTransformRule = '246$1';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:246|[58]\d\d|900)\d{7}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('246(?:(?:2(?:[3568]\d|4[0-57-9])|3(?:5[2-9]|6[0-6])|4(?:46|5\d)|69[5-7]|8(?:[2-5]\d|83))\d|52(?:1[147]|20))\d{3}')
            ->setExampleNumber('2462501234')
            ->setPossibleLengthLocalOnly([7]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:246976|900[2-9]\d\d)\d{4}')
            ->setExampleNumber('9002123456')
            ->setPossibleLengthLocalOnly([7]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('246521[0369]\d{3}|246(?:2(?:2[78]|7[0-4])|4(?:1[024-6]|2\d|3[2-9])|5(?:20|[34]\d|54|7[1-3])|6(?:2\d|38)|7[35]7|9(?:1[89]|63))\d{4}')
            ->setExampleNumber('2464123456')
            ->setPossibleLengthLocalOnly([7]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:00|33|44|55|66|77|88)[2-9]\d{6}')
            ->setExampleNumber('8002123456');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('52(?:3(?:[2-46-9][02-9]\d|5(?:[02-46-9]\d|5[0-46-9]))|4(?:[2-478][02-9]\d|5(?:[034]\d|2[024-9]|5[0-46-9])|6(?:0[1-9]|[2-9]\d)|9(?:[05-9]\d|2[0-5]|49)))\d{4}|52[34][2-9]1[02-9]\d{4}|5(?:00|2[125-9]|33|44|66|77|88)[2-9]\d{6}')
            ->setExampleNumber('5002345678');
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('24631\d{5}')
            ->setExampleNumber('2463101234')
            ->setPossibleLengthLocalOnly([7]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('246(?:292|367|4(?:1[7-9]|3[01]|4[47-9]|67)|7(?:1[2-9]|2\d|3[016]|53))\d{4}')
            ->setExampleNumber('2464301234')
            ->setPossibleLengthLocalOnly([7]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
