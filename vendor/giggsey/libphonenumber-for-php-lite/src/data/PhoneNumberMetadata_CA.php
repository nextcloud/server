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
class PhoneNumberMetadata_CA extends PhoneMetadata
{
    protected const ID = 'CA';
    protected const COUNTRY_CODE = 1;
    protected const NATIONAL_PREFIX = '1';

    protected ?string $nationalPrefixForParsing = '1';
    protected ?string $internationalPrefix = '011';
    protected bool $mobileNumberPortableRegion = true;
    protected bool $sameMobileAndFixedLinePattern = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[2-9]\d{9}|3\d{6}')
            ->setPossibleLength([7, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:04|[23]6|[48]9|5[07]|63)|3(?:06|43|54|6[578]|82)|4(?:03|1[68]|[26]8|3[178]|50|74)|5(?:06|1[49]|48|79|8[147])|6(?:04|[18]3|39|47|72)|7(?:0[59]|42|53|78|8[02])|8(?:[06]7|19|25|7[39])|9(?:0[25]|42))[2-9]\d{6}')
            ->setExampleNumber('5062345678')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900[2-9]\d{6}')
            ->setExampleNumber('9002123456')
            ->setPossibleLength([10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:04|[23]6|[48]9|5[07]|63)|3(?:06|43|54|6[578]|82)|4(?:03|1[68]|[26]8|3[178]|50|74)|5(?:06|1[49]|48|79|8[147])|6(?:04|[18]3|39|47|72)|7(?:0[59]|42|53|78|8[02])|8(?:[06]7|19|25|7[39])|9(?:0[25]|42))[2-9]\d{6}')
            ->setExampleNumber('5062345678')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([10]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:00|33|44|55|66|77|88)[2-9]\d{6}')
            ->setExampleNumber('8002123456')
            ->setPossibleLength([10]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('52(?:3(?:[2-46-9][02-9]\d|5(?:[02-46-9]\d|5[0-46-9]))|4(?:[2-478][02-9]\d|5(?:[034]\d|2[024-9]|5[0-46-9])|6(?:0[1-9]|[2-9]\d)|9(?:[05-9]\d|2[0-5]|49)))\d{4}|52[34][2-9]1[02-9]\d{4}|(?:5(?:2[125-9]|33|44|66|77|88)|6(?:22|33))[2-9]\d{6}')
            ->setExampleNumber('5219023456')
            ->setPossibleLength([10]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('600[2-9]\d{6}')
            ->setExampleNumber('6002012345')
            ->setPossibleLength([10]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('310\d{4}')
            ->setExampleNumber('3101234')
            ->setPossibleLength([7]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
