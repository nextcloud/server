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
class ShortNumberMetadata_IE extends PhoneMetadata
{
    protected const ID = 'IE';
    protected const COUNTRY_CODE = 0;

    protected ?string $internationalPrefix = '';

    public function __construct()
    {
        $this->generalDesc = (new PhoneNumberDesc())
            ->setNationalNumberPattern('[159]\d{2,5}')
            ->setPossibleLength([3, 4, 5, 6]);
        $this->premiumRate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('5[37]\d{3}')
            ->setExampleNumber('53000')
            ->setPossibleLength([5]);
        $this->tollFree = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11(?:2|6\d{3})|999')
            ->setExampleNumber('112')
            ->setPossibleLength([3, 6]);
        $this->emergency = (new PhoneNumberDesc())
            ->setNationalNumberPattern('112|999')
            ->setExampleNumber('112')
            ->setPossibleLength([3]);
        $this->short_code = (new PhoneNumberDesc())
            ->setNationalNumberPattern('11(?:2|6(?:00[06]|1(?:1[17]|23)))|999|(?:1(?:18|9)|5[0137]\d)\d\d')
            ->setExampleNumber('112');
        $this->standard_rate = (new PhoneNumberDesc())
            ->setNationalNumberPattern('51\d{3}')
            ->setExampleNumber('51000')
            ->setPossibleLength([5]);
        $this->carrierSpecific = (new PhoneNumberDesc())
            ->setNationalNumberPattern('51210')
            ->setExampleNumber('51210')
            ->setPossibleLength([5]);
        $this->smsServices = (new PhoneNumberDesc())
            ->setNationalNumberPattern('51210|(?:118|5[037]\d)\d\d')
            ->setExampleNumber('11800')
            ->setPossibleLength([5]);
    }
}
