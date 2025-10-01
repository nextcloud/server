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
class PhoneNumberMetadata_PG extends PhoneMetadata
{
    protected const ID = 'PG';
    protected const COUNTRY_CODE = 675;

    protected ?string $internationalPrefix = '00|140[1-3]';
    protected ?string $preferredInternationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:180|[78]\d{3})\d{4}|(?:[2-589]\d|64)\d{5}')
            ->setPossibleLength([7, 8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:7\d|8[1-38])\d{6}')
            ->setExampleNumber('70123456')
            ->setPossibleLength([8]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:(?:3[0-2]|4[257]|5[34]|9[78])\d|64[1-9]|85[02-46-9])\d{4}')
            ->setExampleNumber('3123456')
            ->setPossibleLength([7]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['18|[2-69]|85'])
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{4})(\d{4})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[78]'])
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('180\d{4}')
            ->setExampleNumber('1801234')
            ->setPossibleLength([7]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('2(?:0[0-57]|7[568])\d{4}')
            ->setExampleNumber('2751234')
            ->setPossibleLength([7]);
        $this->pager = (new PhoneNumberDesc())
            ->setNationalNumberPattern('27[01]\d{4}')
            ->setExampleNumber('2700123')
            ->setPossibleLength([7]);
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
