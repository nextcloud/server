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
class PhoneNumberMetadata_IM extends PhoneMetadata
{
    protected const ID = 'IM';
    protected const COUNTRY_CODE = 44;
    protected const LEADING_DIGITS = '74576|(?:16|7[56])24';
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '([25-8]\d{5})$|0';
    protected ?string $internationalPrefix = '00';
    protected ?string $nationalPrefixTransformRule = '1624$1';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1624\d{6}|(?:[3578]\d|90)\d{8}')
            ->setPossibleLengthLocalOnly([6])
            ->setPossibleLength([10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('76245[06]\d{4}|7(?:4576|[59]24\d|624[0-4689])\d{5}')
            ->setExampleNumber('7924123456');
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:440[49]06|72299\d)\d{3}|(?:8(?:45|70)|90[0167])624\d{4}')
            ->setExampleNumber('9016247890');
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1624(?:230|[5-8]\d\d)\d{3}')
            ->setExampleNumber('1624756789')
            ->setPossibleLengthLocalOnly([6]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('808162\d{4}')
            ->setExampleNumber('8081624567');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70\d{8}')
            ->setExampleNumber('7012345678');
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('56\d{8}')
            ->setExampleNumber('5612345678');
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('3440[49]06\d{3}|(?:3(?:08162|3\d{4}|45624|7(?:0624|2299))|55\d{4})\d{4}')
            ->setExampleNumber('5512345678');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
