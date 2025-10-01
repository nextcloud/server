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
class PhoneNumberMetadata_BO extends PhoneMetadata
{
    protected const ID = 'BO';
    protected const COUNTRY_CODE = 591;
    protected const NATIONAL_PREFIX = '0';

    protected ?string $nationalPrefixForParsing = '0(1\d)?';
    protected ?string $internationalPrefix = '00(?:1\d)?';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8001\d{5}|(?:[2-467]\d|50)\d{6}')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([8, 9]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[67]\d{7}')
            ->setExampleNumber('71234567')
            ->setPossibleLength([8]);
        $this->premiumRate = PhoneNumberDesc::empty();
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:2(?:2\d\d|5(?:11|[258]\d|9[67])|6(?:12|2\d|9[34])|8(?:2[34]|39|62))|3(?:3\d\d|4(?:6\d|8[24])|8(?:25|42|5[257]|86|9[25])|9(?:[27]\d|3[2-4]|4[248]|5[24]|6[2-6]))|4(?:4\d\d|6(?:11|[24689]\d|72)))\d{4}')
            ->setExampleNumber('22123456')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([8]);
        $this->numberFormat = [
            (new NumberFormat())
                ->setPattern('(\d)(\d{7})')
                ->setFormat('$1 $2')
                ->setLeadingDigitsPattern(['[235]|4[46]'])
                ->setDomesticCarrierCodeFormattingRule('0$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{8})')
                ->setFormat('$1')
                ->setLeadingDigitsPattern(['[67]'])
                ->setDomesticCarrierCodeFormattingRule('0$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
            (new NumberFormat())
                ->setPattern('(\d{3})(\d{2})(\d{4})')
                ->setFormat('$1 $2 $3')
                ->setLeadingDigitsPattern(['8'])
                ->setDomesticCarrierCodeFormattingRule('0$CC $1')
                ->setNationalPrefixOptionalWhenFormatting(false),
        ];
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8001[07]\d{4}')
            ->setExampleNumber('800171234')
            ->setPossibleLength([9]);
        $this->sharedCost = PhoneNumberDesc::empty();
        $this->personalNumber = PhoneNumberDesc::empty();
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('50\d{6}')
            ->setExampleNumber('50123456')
            ->setPossibleLengthLocalOnly([7])
            ->setPossibleLength([8]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = PhoneNumberDesc::empty();
        $this->voicemail = PhoneNumberDesc::empty();
        $this->noInternationalDialling = (new PhoneNumberDesc())
            ->setNationalNumberPattern('8001[07]\d{4}')
            ->setPossibleLength([9]);
    }
}
