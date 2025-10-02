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
class PhoneNumberMetadata_HK extends PhoneMetadata
{
    protected const ID = 'HK';
    protected const COUNTRY_CODE = 852;

    protected ?string $internationalPrefix = '00(?:30|5[09]|[126-9]?)';
    protected ?string $preferredInternationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8[0-46-9]\d{6,7}|9\d{4,7}|(?:[2-7]|9\d{3})\d{7}')
            ->setPossibleLength([5, 6, 7, 8, 9, 11]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:4(?:44[0-35-9]|6(?:4[0-57-9]|6[0-4])|7(?:3[0-4]|4[0-48]|6[0-5]))|5(?:35[4-8]|73[0-6]|95[0-8])|6(?:26[013-8]|(?:66|78)[0-5])|70(?:7[1-8]|8[0-8])|84(?:4[0-2]|8[0-35-9])|9(?:29[013-9]|39[014-9]|59[0-4]|899))\d{4}|(?:4(?:4[0-35-9]|6[0-357-9]|7[0-25])|5(?:[1-59][0-46-9]|6[0-4689]|7[0-246-9])|6(?:0[1-9]|[13-59]\d|[268][0-57-9]|7[0-79])|70[1-59]|84[0-39]|9(?:0[1-9]|1[02-9]|[2358][0-8]|[467]\d))\d{5}')
            ->setExampleNumber('51234567')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('900(?:[0-24-9]\d{7}|3\d{1,4})')
            ->setExampleNumber('90012345678')
            ->setPossibleLength([5, 6, 7, 8, 11]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:[13-9]\d|2[013-9])\d|3(?:(?:[1569][0-24-9]|4[0-246-9]|7[0-24-69])\d|8(?:4[0-8]|[579]\d|6[0-5]))|58(?:0[1-9]|1[2-9]))\d{4}')
            ->setExampleNumber('21234567')
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2,5})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['900', '9003'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[2-7]|8[1-4]|9(?:0[1-9]|[1-8])'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3 $4')
                ->setLeadingDigitsPattern(['9'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800\d{6}')
            ->setExampleNumber('800123456')
            ->setPossibleLength([9]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8(?:1[0-4679]\d|2(?:[0-36]\d|7[0-4])|3(?:[034]\d|2[09]|70))\d{4}')
            ->setExampleNumber('81123456')
            ->setPossibleLength([8]);
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('7(?:1(?:0[0-38]|1[0-3679]|3[013]|69|9[0136])|2(?:[02389]\d|1[18]|7[27-9])|3(?:[0-38]\d|7[0-369]|9[2357-9])|47\d|5(?:[178]\d|5[0-5])|6(?:0[0-7]|2[236-9]|[35]\d)|7(?:[27]\d|8[7-9])|8(?:[23689]\d|7[1-9])|9(?:[025]\d|6[0-246-8]|7[0-36-9]|8[238]))\d{4}')
            ->setExampleNumber('71123456')
            ->setPossibleLength([8]);
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('30(?:0[1-9]|[15-7]\d|2[047]|89)\d{4}')
            ->setExampleNumber('30161234')
            ->setPossibleLength([8]);
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
