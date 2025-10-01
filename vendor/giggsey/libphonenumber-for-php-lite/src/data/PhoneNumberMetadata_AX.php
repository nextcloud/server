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
class PhoneNumberMetadata_AX extends PhoneMetadata
{
    protected const ID = 'AX';
    protected const COUNTRY_CODE = 358;
    protected const LEADING_DIGITS = '18';
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00|99(?:[01469]|5(?:[14]1|3[23]|5[59]|77|88|9[09]))';
    protected ?string $preferredInternationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2\d{4,9}|35\d{4,5}|(?:60\d\d|800)\d{4,6}|7\d{5,11}|(?:[14]\d|3[0-46-9]|50)\d{4,8}')
            ->setPossibleLength([5, 6, 7, 8, 9, 10, 11, 12]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('4946\d{2,6}|(?:4[0-8]|50)\d{4,8}')
            ->setExampleNumber('412345678')
            ->setPossibleLength([6, 7, 8, 9, 10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[67]00\d{5,6}')
            ->setExampleNumber('600123456')
            ->setPossibleLength([8, 9]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('18[1-8]\d{3,6}')
            ->setExampleNumber('181234567')
            ->setPossibleLength([6, 7, 8, 9]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{4,6}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([7, 8, 9]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('20\d{4,8}|60[12]\d{5,6}|7(?:099\d{4,5}|5[03-9]\d{3,7})|20[2-59]\d\d|(?:606|7(?:0[78]|1|3\d))\d{7}|(?:10|29|3[09]|70[1-5]\d)\d{4,8}')
            ->setExampleNumber('10112345');
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
