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
class PhoneNumberMetadata_GG extends PhoneMetadata
{
    protected const ID = 'GG';
    protected const COUNTRY_CODE = 44;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '([25-9]\d{5})$|0';
    protected ?string $internationalPrefix = '00';
    protected ?string $nationalPrefixTransformRule = '1481$1';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1481|[357-9]\d{3})\d{6}|8\d{6}(?:\d{2})?')
            ->setPossibleLengthLocalOnly([6])
            ->setPossibleLength([7, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7(?:(?:781|839)\d|911[17])\d{5}')
            ->setExampleNumber('7781123456')
            ->setPossibleLength([10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:8(?:4[2-5]|7[0-3])|9(?:[01]\d|8[0-3]))\d{7}|845464\d')
            ->setExampleNumber('9012345678')
            ->setPossibleLength([7, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1481[25-9]\d{5}')
            ->setExampleNumber('1481256789')
            ->setPossibleLengthLocalOnly([6])
            ->setPossibleLength([10]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[08]\d{7}|800\d{6}|8001111')
            ->setExampleNumber('8001234567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70\d{8}')
            ->setExampleNumber('7012345678')
            ->setPossibleLength([10]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('56\d{8}')
            ->setExampleNumber('5612345678')
            ->setPossibleLength([10]);
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('76(?:464|652)\d{5}|76(?:0[0-28]|2[356]|34|4[01347]|5[49]|6[0-369]|77|8[14]|9[139])\d{6}')
            ->setExampleNumber('7640123456')
            ->setPossibleLength([10]);
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3[0347]|55)\d{8}')
            ->setExampleNumber('5512345678')
            ->setPossibleLength([10]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
