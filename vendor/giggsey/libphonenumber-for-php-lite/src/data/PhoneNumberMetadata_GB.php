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
class PhoneNumberMetadata_GB extends PhoneMetadata
{
    protected const ID = 'GB';
    protected const COUNTRY_CODE = 44;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0';
    protected ?string $internationalPrefix = '00';
    protected ?string $preferredExtnPrefix = ' x';
    protected bool $mainCountryForCode = true;
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[1-357-9]\d{9}|[18]\d{8}|8\d{6}')
            ->setPossibleLengthLocalOnly([4, 5, 6, 8])
            ->setPossibleLength([7, 9, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7(?:457[0-57-9]|700[01]|911[028])\d{5}|7(?:[1-3]\d\d|4(?:[0-46-9]\d|5[0-689])|5(?:0[0-8]|[13-9]\d|2[0-35-9])|7(?:0[1-9]|[1-7]\d|8[02-9]|9[0-689])|8(?:[014-9]\d|[23][0-8])|9(?:[024-9]\d|1[02-9]|3[0-689]))\d{6}')
            ->setExampleNumber('7400123456')
            ->setPossibleLength([10]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:8(?:4[2-5]|7[0-3])|9(?:[01]\d|8[2-49]))\d{7}|845464\d')
            ->setExampleNumber('9012345678')
            ->setPossibleLength([7, 10]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:1(?:1(?:3(?:[0-58]\d\d|73[0-35])|4(?:(?:[0-5]\d|70)\d|69[7-9])|(?:(?:5[0-26-9]|[78][0-49])\d|6(?:[0-4]\d|50))\d)|(?:2(?:(?:0[024-9]|2[3-9]|3[3-79]|4[1-689]|[58][02-9]|6[0-47-9]|7[013-9]|9\d)\d|1(?:[0-7]\d|8[0-3]))|(?:3(?:0\d|1[0-8]|[25][02-9]|3[02-579]|[468][0-46-9]|7[1-35-79]|9[2-578])|4(?:0[03-9]|[137]\d|[28][02-57-9]|4[02-69]|5[0-8]|[69][0-79])|5(?:0[1-35-9]|[16]\d|2[024-9]|3[015689]|4[02-9]|5[03-9]|7[0-35-9]|8[0-468]|9[0-57-9])|6(?:0[034689]|1\d|2[0-35689]|[38][013-9]|4[1-467]|5[0-69]|6[13-9]|7[0-8]|9[0-24578])|7(?:0[0246-9]|2\d|3[0236-8]|4[03-9]|5[0-46-9]|6[013-9]|7[0-35-9]|8[024-9]|9[02-9])|8(?:0[35-9]|2[1-57-9]|3[02-578]|4[0-578]|5[124-9]|6[2-69]|7\d|8[02-9]|9[02569])|9(?:0[02-589]|[18]\d|2[02-689]|3[1-57-9]|4[2-9]|5[0-579]|6[2-47-9]|7[0-24578]|9[2-57]))\d)\d)|2(?:0[013478]|3[0189]|4[017]|8[0-46-9]|9[0-2])\d{3})\d{4}|1(?:2(?:0(?:46[1-4]|87[2-9])|545[1-79]|76(?:2\d|3[1-8]|6[1-6])|9(?:7(?:2[0-4]|3[2-5])|8(?:2[2-8]|7[0-47-9]|8[3-5])))|3(?:6(?:38[2-5]|47[23])|8(?:47[04-9]|64[0157-9]))|4(?:044[1-7]|20(?:2[23]|8\d)|6(?:0(?:30|5[2-57]|6[1-8]|7[2-8])|140)|8(?:052|87[1-3]))|5(?:2(?:4(?:3[2-79]|6\d)|76\d)|6(?:26[06-9]|686))|6(?:06(?:4\d|7[4-79])|295[5-7]|35[34]\d|47(?:24|61)|59(?:5[08]|6[67]|74)|9(?:55[0-4]|77[23]))|7(?:26(?:6[13-9]|7[0-7])|(?:442|688)\d|50(?:2[0-3]|[3-68]2|76))|8(?:27[56]\d|37(?:5[2-5]|8[239])|843[2-58])|9(?:0(?:0(?:6[1-8]|85)|52\d)|3583|4(?:66[1-8]|9(?:2[01]|81))|63(?:23|3[1-4])|9561))\d{3}')
            ->setExampleNumber('1212345678')
            ->setPossibleLengthLocalOnly([4, 5, 6, 7, 8])
            ->setPossibleLength([9, 10]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['800', '8001', '80011', '800111', '8001111'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{2})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['845', '8454', '84546', '845464'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['800'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{5})(\d{4,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['1(?:38|5[23]|69|76|94)', '1(?:(?:38|69)7|5(?:24|39)|768|946)', '1(?:3873|5(?:242|39[4-6])|(?:697|768)[347]|9467)'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{5,6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['1(?:[2-69][02-9]|[78])'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{4})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[25]|7(?:0|6[02-9])', '[25]|7(?:0|6(?:[03-9]|2[356]))'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{6})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['[1389]'])
                ->setNationalPrefixFormattingRule('0$1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
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
