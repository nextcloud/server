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
class PhoneNumberMetadata_EE extends PhoneMetadata
{
    protected const ID = 'EE';
    protected const COUNTRY_CODE = 372;

    protected ?string $internationalPrefix = '00';
    protected bool $mobileNumberPortableRegion = true;

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8\d{9}|[4578]\d{7}|(?:[3-8]\d|90)\d{5}')
            ->setPossibleLength([7, 8, 10]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:5\d{5}|8(?:1(?:0(?:0(?:00|[178]\d)|[3-9]\d\d)|(?:1(?:0[2-6]|1\d)|(?:2[0-59]|[3-79]\d)\d)\d)|2(?:0(?:0(?:00|4\d)|(?:19|[2-7]\d)\d)|(?:(?:[124-69]\d|3[5-9])\d|7(?:[0-79]\d|8[13-9])|8(?:[2-6]\d|7[01]))\d)|[349]\d{4}))\d\d|5(?:(?:[02]\d|5[0-478])\d|1(?:[0-8]\d|95)|6(?:4[0-4]|5[1-589]))\d{3}')
            ->setExampleNumber('51234567')
            ->setPossibleLength([7, 8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:40\d\d|900)\d{4}')
            ->setExampleNumber('9001234')
            ->setPossibleLength([7, 8]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:3[23589]|4[3-8]|6\d|7[1-9]|88)\d{5}')
            ->setExampleNumber('3212345')
            ->setPossibleLength([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern([
                    '[369]|4[3-8]|5(?:[0-2]|5[0-478]|6[45])|7[1-9]|88',
                    '[369]|4[3-8]|5(?:[02]|1(?:[0-8]|95)|5[0-478]|6(?:4[0-4]|5[1-589]))|7[1-9]|88',
                ])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3,4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[45]|8(?:00|[1-49])', '[45]|8(?:00[1-9]|[1-49])'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{2})(\d{2})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['7'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{3})(\d{3})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800(?:(?:0\d\d|1)\d|[2-9])\d{3}')
            ->setExampleNumber('80012345');
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('70[0-2]\d{5}')
            ->setExampleNumber('70012345')
            ->setPossibleLength([8]);
        $this->voip = PhoneNumberDesc::empty();
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('800[2-9]\d{3}')
            ->setPossibleLength([7]);
    }
}
