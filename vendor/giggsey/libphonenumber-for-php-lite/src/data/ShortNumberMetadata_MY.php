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
class ShortNumberMetadata_MY extends PhoneMetadata
{
    protected const ID = 'MY';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1369]\d{2,4}')
            ->setPossibleLength([3, 4, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|999')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|999')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:0[01348]|1(?:[02]|1[128]|311)|2(?:0[125]|[13-6]|2\d{0,2})|(?:3[1-35-79]|7[45])\d\d?|5(?:454|5\d\d?|77|888|999?)|8(?:18?|2|8[18])|9(?:[124]\d?|68|71|9[0679]))|66628|99[1-469]|13[5-7]|(?:1(?:0[569]|309|5[12]|7[136-9]|9[03])|3[23679]\d\d)\d')
            ->setExampleNumber('100');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('666\d\d')
            ->setExampleNumber('66600')
            ->setPossibleLength([5]);
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3[23679]\d|666)\d\d')
            ->setExampleNumber('32000')
            ->setPossibleLength([5]);
    }
}
