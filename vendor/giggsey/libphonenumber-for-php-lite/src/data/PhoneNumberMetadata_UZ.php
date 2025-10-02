<?php

/**
 * libphonenumber-for-php-lite data file
 * This file has been @generated from libphonenumber data
 * Do not modify!
 * @internal
 */

declare(strict_types=1);

namespace libphonenumber\data;

use libphonenumber\NumberFormat;
use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumberDesc;

/**
 * @internal
 */
class PhoneNumberMetadata_UZ extends PhoneMetadata
{
    protected const ID = 'UZ';
    protected const COUNTRY_CODE = 998;

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:20|33|[5-9]\d)\d{7}')
            ->setPossibleLength([9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:(?:[25]0|33|8[78]|9[0-57-9])\d{3}|6(?:1(?:2(?:2[01]|98)|35[0-4]|50\d|61[23]|7(?:[01][017]|4\d|55|9[5-9]))|2(?:(?:11|7\d)\d|2(?:[12]1|9[01379])|5(?:[126]\d|3[0-4]))|5(?:19[01]|2(?:27|9[26])|(?:30|59|7\d)\d)|6(?:2(?:1[5-9]|2[0367]|38|41|52|60)|(?:3[79]|9[0-3])\d|4(?:56|83)|7(?:[07]\d|1[017]|3[07]|4[047]|5[057]|67|8[0178]|9[79]))|7(?:2(?:24|3[237]|4[5-9]|7[15-8])|5(?:7[12]|8[0589])|7(?:0\d|[39][07])|9(?:0\d|7[079])))|7(?:[07]\d{3}|2(?:2(?:2[79]|95)|3(?:2[5-9]|6[0-6])|57\d|7(?:0\d|1[17]|2[27]|3[37]|44|5[057]|66|88))|3(?:2(?:1[0-6]|21|3[469]|7[159])|(?:33|9[4-6])\d|5(?:0[0-4]|5[579]|9\d)|7(?:[0-3579]\d|4[0467]|6[67]|8[078]))|4(?:2(?:29|5[0257]|6[0-7]|7[1-57])|5(?:1[0-4]|8\d|9[5-9])|7(?:0\d|1[024589]|2[0-27]|3[0137]|[46][07]|5[01]|7[5-9]|9[079])|9(?:7[015-9]|[89]\d))|5(?:112|2(?:0\d|2[29]|[49]4)|3[1568]\d|52[6-9]|7(?:0[01578]|1[017]|[23]7|4[047]|[5-7]\d|8[78]|9[079]))|9(?:22[128]|3(?:2[0-4]|7\d)|57[02569]|7(?:2[05-9]|3[37]|4\d|60|7[2579]|87|9[07]))))\d{4}')
            ->setExampleNumber('912345678');
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:55\d\d|6(?:1(?:22|3[124]|4[1-4]|5[1-3578]|64)|2(?:22|3[0-57-9]|41)|5(?:22|3[3-7]|5[024-8])|[69]\d\d|7(?:[23]\d|7[69]))|7(?:0(?:5[4-9]|6[0146]|7[124-6]|9[135-8])|[168]\d\d|2(?:22|3[13-57-9]|4[1-3579]|5[14])|3(?:2\d|3[1578]|4[1-35-7]|5[1-57]|61)|4(?:2\d|3[1-579]|7[1-79])|5(?:22|5[1-9]|6[1457])|9(?:22|5[1-9])))\d{5}')
            ->setExampleNumber('669050123');
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['[235-9]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = PhoneNumberDesc::empty();
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
