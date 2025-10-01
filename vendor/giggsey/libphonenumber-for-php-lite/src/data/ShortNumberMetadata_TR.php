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
class ShortNumberMetadata_TR extends PhoneMetadata
{
    protected const ID = 'TR';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-9]\d{2,4}')
            ->setPossibleLength([3, 4, 5]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[02]|22|3[126]|4[04]|5[15-9]|6[18]|77|83)')
            ->setExampleNumber('110')
            ->setPossibleLength([3]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1[02]|55)')
            ->setExampleNumber('110')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:1(?:[02-79]|8(?:1[018]|2[0245]|3[2-4]|42|5[058]|6[06]|7[07]|8[01389]|9[089]))|3(?:37|[58]6|65)|471|5(?:07|78)|6(?:[02]6|99)|8(?:63|95))|2(?:077|268|4(?:17|23)|5(?:7[26]|82)|6[14]4|8\d\d|9(?:30|89))|3(?:0(?:05|72)|353|4(?:06|30|64)|502|674|747|851|9(?:1[29]|60))|4(?:0(?:25|3[12]|[47]2)|3(?:3[13]|[89]1)|439|5(?:43|55)|717|832)|5(?:145|290|[4-6]\d\d|772|833|9(?:[06]1|92))|6(?:236|6(?:12|39|8[59])|769)|7890|8(?:688|7(?:28|65)|85[06])|9(?:159|290)|1[2-9]\d')
            ->setExampleNumber('110');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:285|542)0')
            ->setExampleNumber('2850')
            ->setPossibleLength([4]);
        $this->carrierSpecific = PhoneNumberDesc::empty();
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('1(?:3(?:37|[58]6|65)|4(?:4|71)|5(?:07|78)|6(?:[02]6|99)|8(?:3|63|95))|(?:2(?:07|26|4[12]|5[78]|6[14]|8\d|9[38])|3(?:0[07]|[38]5|4[036]|50|67|74|9[16])|4(?:0[2-47]|3[389]|[48]3|5[45]|71)|5(?:14|29|[4-6]\d|77|83|9[069])|6(?:23|6[138]|76)|789|8(?:68|7[26]|85)|9(?:15|29))\d')
            ->setExampleNumber('144')
            ->setPossibleLength([3, 4]);
    }
}
