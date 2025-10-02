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
class PhoneNumberMetadata_JE extends PhoneMetadata
{
    protected const ID = 'JE';
    protected const COUNTRY_CODE = 44;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '([0-24-8]\d{5})$|0';
    protected ?string $internationalPrefix = '00';
    protected ?string $nationalPrefixTransformRule = '1534$1';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1534\d{6}|(?:[3578]\d|90)\d{8}')
            ->setPossibleLengthLocalOnly([6])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7(?:(?:(?:50|82)9|937)\d|7(?:00[378]|97\d))\d{5}')
            ->setExampleNumber('7797712345');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:8(?:4(?:4(?:4(?:05|42|69)|703)|5(?:041|800))|7(?:0002|1206))|90(?:066[59]|1810|71(?:07|55)))\d{4}')
            ->setExampleNumber('9018105678');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1534[0-24-8]\d{5}')
            ->setExampleNumber('1534456789')
            ->setPossibleLengthLocalOnly([6]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80(?:07(?:35|81)|8901)\d{4}')
            ->setExampleNumber('8007354567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('701511\d{4}')
            ->setExampleNumber('7015115678');
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('56\d{8}')
            ->setExampleNumber('5612345678');
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('76(?:464|652)\d{5}|76(?:0[0-28]|2[356]|34|4[01347]|5[49]|6[0-369]|77|8[14]|9[139])\d{6}')
            ->setExampleNumber('7640123456');
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3(?:0(?:07(?:35|81)|8901)|3\d{4}|4(?:4(?:4(?:05|42|69)|703)|5(?:041|800))|7(?:0002|1206))|55\d{4})\d{4}')
            ->setExampleNumber('5512345678');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
