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
class ShortNumberMetadata_EE extends PhoneMetadata
{
    protected const ID = 'EE';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:18(?:00|[12458]\d?)|2(?:0(?:[02-46-8]\d?|1[0-36])|1(?:[0-4]\d?|6[06])|2(?:[0-4]\d?|5[25])|[367]|4(?:0[04]|[12]\d?|4[24]|54)|55[12457])|3(?:0(?:[02]\d?|1[13578]|3[356])|1[1347]|2[02-5]|3(?:[01347]\d?|2[023]|88)|4(?:[35]\d?|4[34])|5(?:3[134]|5[035])|666)|4(?:2(?:00|4\d?)|4(?:0[01358]|1[024]|50|7\d?)|900)|5(?:0[0-35]|1(?:[1267]\d?|5[0-7]|82)|2(?:[014-6]\d?|22)|330|4(?:[35]\d?|44)|5(?:00|[1-69]\d?)|9(?:[159]\d?|[38]0|77))|6(?:1(?:00|1[19]|[35-9]\d?)|2(?:2[26]|[68]\d?)|3(?:22|36|6[36])|5|6(?:[0-359]\d?|6[0-26])|7(?:00|55|7\d?|8[89])|9(?:00|1\d?|69))|7(?:0(?:[023]\d?|1[0578])|1(?:00|2[034]|[4-9]\d?)|2(?:[07]\d?|20|44)|7(?:[0-57]\d?|9[79])|8(?:0[08]|2\d?|8[0178])|9(?:00|97))|8(?:1[127]|8[1268]|9[269])|9(?:0(?:[02]\d?|69|9[0269])|1[1-3689]|21))')
            ->setExampleNumber('123')
            ->setPossibleLength([3, 4, 5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:[02]|6\d{3})|2(?:05|28)|3(?:014|3(?:21|5\d?)|660)|492|5(?:1[03]|410|501)|6(?:112|333|644)|7(?:012|127|89)|8(?:10|8[57])|9(?:0[134]|14))')
            ->setExampleNumber('110');
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11[02]')
            ->setExampleNumber('110')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:[02-579]|6(?:000|111)|8(?:[09]\d|[1-8]))|2[36-9]|3[7-9]|4[05-7]|5[6-8]|6[05]|7[3-6]|8[02-7]|9[3-9])|1(?:2[0-245]|3[0-6]|4[1-489]|5[0-59]|6[1-46-9]|7[0-27-9]|8[189]|9[0-2])\d\d?')
            ->setExampleNumber('110');
        $this->standard_rate = PhoneNumberDesc::empty();
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:18[1258]|2(?:0(?:1[036]|[46]\d?)|166|21|4(?:0[04]|1\d?|5[47])|[67])|3(?:0(?:1[13-578]|2\d?|3[56])|1[15]|2[045]|3(?:[13]\d?|2[13])|43|5(?:00|3[34]|53))|44(?:0[0135]|14|50|7\d?)|5(?:05|1(?:[12]\d?|5[1246]|8[12])|2(?:[01]\d?|22)|3(?:00|3[03])|4(?:15|5\d?)|500|9(?:5\d?|77|80))|6(?:1[35-8]|226|3(?:22|3[36]|66)|644|7(?:00|7\d?|89)|9(?:00|69))|7(?:01[258]|1(?:00|[15]\d?)|2(?:44|7\d?)|8(?:00|87|9\d?))|8(?:1[128]|8[56]|9(?:[26]\d?|77))|90(?:2\d?|69|92))')
            ->setExampleNumber('126')
            ->setPossibleLength([3, 4, 5]);
    }
}
