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
class PhoneNumberMetadata_SJ extends PhoneMetadata
{
    protected const ID = 'SJ';
    protected const COUNTRY_CODE = 47;
    protected const LEADING_DIGITS = '79';

    protected ?string $internationalPrefix = '00';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('0\d{4}|(?:[489]\d|79)\d{6}')
            ->setPossibleLength([5, 8]);
        $this->mobile = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:4[015-8]|9\d)\d{6}')
            ->setExampleNumber('41234567')
            ->setPossibleLength([8]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('82[09]\d{5}')
            ->setExampleNumber('82012345')
            ->setPossibleLength([8]);
        $this->fixedLine = (new PhoneNumberDesc())
            ->setNationalNumberPattern('79\d{6}')
            ->setExampleNumber('79123456')
            ->setPossibleLength([8]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('80[01]\d{5}')
            ->setExampleNumber('80012345')
            ->setPossibleLength([8]);
        $this->sharedCost = (new PhoneNumberDesc())
            ->setNationalNumberPattern('810(?:0[0-6]|[2-8]\d)\d{3}')
            ->setExampleNumber('81021234')
            ->setPossibleLength([8]);
        $this->personalNumber = (new PhoneNumberDesc())
            ->setNationalNumberPattern('880\d{5}')
            ->setExampleNumber('88012345')
            ->setPossibleLength([8]);
        $this->voip = (new PhoneNumberDesc())
            ->setNationalNumberPattern('85[0-5]\d{5}')
            ->setExampleNumber('85012345')
            ->setPossibleLength([8]);
        $this->pager = PhoneNumberDesc::empty();
        $this->uan = (new PhoneNumberDesc())
            ->setNationalNumberPattern('(?:0[235-9]|81(?:0(?:0[7-9]|1\d)|5\d\d))\d{3}')
            ->setExampleNumber('02000');
        $this->voicemail = (new PhoneNumberDesc())
            ->setNationalNumberPattern('81[23]\d{5}')
            ->setExampleNumber('81212345')
            ->setPossibleLength([8]);
        $this->noInternationalDialling = PhoneNumberDesc::empty();
    }
}
