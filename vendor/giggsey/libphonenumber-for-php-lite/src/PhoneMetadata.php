<?php

declare(strict_types=1);

namespace libphonenumber;

use function count;

/**
 * Class PhoneMetadata
 * @package libphonenumber
 * @internal Used internally, and can change at any time
 */
class PhoneMetadata
{
    /**
     * @var string|null
     */
    protected const ID = null;
    /**
     * @var int|null
     */
    protected const COUNTRY_CODE = null;
    /**
     * @var string|null
     */
    protected const LEADING_DIGITS = null;
    /**
     * @var string|null
     */
    protected const NATIONAL_PREFIX = null;
    protected ?string $nationalPrefixForParsing = null;
    protected ?string $internationalPrefix = null;
    protected ?string $preferredInternationalPrefix = null;
    protected ?string $nationalPrefixTransformRule = null;
    protected ?string $preferredExtnPrefix = null;
    protected bool $mainCountryForCode = false;
    protected bool $mobileNumberPortableRegion = false;
    protected ?PhoneNumberDesc $generalDesc = null;
    protected ?PhoneNumberDesc $mobile = null;
    protected ?PhoneNumberDesc $premiumRate = null;
    protected ?PhoneNumberDesc $fixedLine = null;
    protected bool $sameMobileAndFixedLinePattern = false;
    /**
     * @var NumberFormat[]
     */
    protected array $numberFormat = [];
    protected ?PhoneNumberDesc $tollFree = null;
    protected ?PhoneNumberDesc $sharedCost = null;
    protected ?PhoneNumberDesc $personalNumber = null;
    protected ?PhoneNumberDesc $voip = null;
    protected ?PhoneNumberDesc $pager = null;
    protected ?PhoneNumberDesc $uan = null;
    protected ?PhoneNumberDesc $emergency = null;
    protected ?PhoneNumberDesc $voicemail = null;
    protected ?PhoneNumberDesc $short_code = null;
    protected ?PhoneNumberDesc $standard_rate = null;
    protected ?PhoneNumberDesc $carrierSpecific = null;
    protected ?PhoneNumberDesc $smsServices = null;
    protected ?PhoneNumberDesc $noInternationalDialling = null;
    /**
     * @var NumberFormat[]
     */
    protected array $intlNumberFormat = [];

    public function isMainCountryForCode(): bool
    {
        return $this->mainCountryForCode;
    }

    public function getMainCountryForCode(): bool
    {
        return $this->mainCountryForCode;
    }

    public function numberFormatSize(): int
    {
        return count($this->numberFormat);
    }

    public function getNumberFormat(int $index): NumberFormat
    {
        return $this->numberFormat[$index];
    }

    public function intlNumberFormatSize(): int
    {
        return count($this->intlNumberFormat);
    }

    public function getIntlNumberFormat(int $index): NumberFormat
    {
        return $this->intlNumberFormat[$index];
    }

    public function hasGeneralDesc(): bool
    {
        return $this->generalDesc !== null;
    }

    public function getGeneralDesc(): ?PhoneNumberDesc
    {
        return $this->generalDesc;
    }

    public function hasFixedLine(): bool
    {
        return $this->fixedLine !== null;
    }

    public function getFixedLine(): ?PhoneNumberDesc
    {
        return $this->fixedLine;
    }

    public function hasMobile(): bool
    {
        return $this->mobile !== null;
    }

    public function getMobile(): ?PhoneNumberDesc
    {
        return $this->mobile;
    }

    public function getTollFree(): ?PhoneNumberDesc
    {
        return $this->tollFree;
    }

    public function getPremiumRate(): ?PhoneNumberDesc
    {
        return $this->premiumRate;
    }

    public function getSharedCost(): ?PhoneNumberDesc
    {
        return $this->sharedCost;
    }

    public function getPersonalNumber(): ?PhoneNumberDesc
    {
        return $this->personalNumber;
    }

    public function getVoip(): ?PhoneNumberDesc
    {
        return $this->voip;
    }

    public function getPager(): ?PhoneNumberDesc
    {
        return $this->pager;
    }

    public function getUan(): ?PhoneNumberDesc
    {
        return $this->uan;
    }

    public function hasEmergency(): bool
    {
        return $this->emergency !== null;
    }

    public function getEmergency(): ?PhoneNumberDesc
    {
        return $this->emergency;
    }

    public function getVoicemail(): ?PhoneNumberDesc
    {
        return $this->voicemail;
    }

    public function getShortCode(): ?PhoneNumberDesc
    {
        return $this->short_code;
    }


    public function getStandardRate(): ?PhoneNumberDesc
    {
        return $this->standard_rate;
    }

    public function getCarrierSpecific(): ?PhoneNumberDesc
    {
        return $this->carrierSpecific;
    }

    public function getSmsServices(): ?PhoneNumberDesc
    {
        return $this->smsServices;
    }

    public function getNoInternationalDialling(): ?PhoneNumberDesc
    {
        return $this->noInternationalDialling;
    }


    public function getId(): ?string
    {
        return static::ID;
    }

    public function getCountryCode(): ?int
    {
        return static::COUNTRY_CODE;
    }

    public function getInternationalPrefix(): ?string
    {
        return $this->internationalPrefix;
    }


    public function hasPreferredInternationalPrefix(): bool
    {
        return ($this->preferredInternationalPrefix !== null);
    }

    public function getPreferredInternationalPrefix(): ?string
    {
        return $this->preferredInternationalPrefix;
    }

    public function hasNationalPrefix(): bool
    {
        return static::NATIONAL_PREFIX !== null;
    }

    public function getNationalPrefix(): ?string
    {
        return static::NATIONAL_PREFIX;
    }

    public function hasPreferredExtnPrefix(): bool
    {
        return $this->preferredExtnPrefix !== null;
    }

    public function getPreferredExtnPrefix(): ?string
    {
        return $this->preferredExtnPrefix;
    }

    public function hasNationalPrefixForParsing(): bool
    {
        return $this->nationalPrefixForParsing !== null;
    }

    public function getNationalPrefixForParsing(): ?string
    {
        return $this->nationalPrefixForParsing;
    }

    public function getNationalPrefixTransformRule(): ?string
    {
        return $this->nationalPrefixTransformRule;
    }

    public function getSameMobileAndFixedLinePattern(): bool
    {
        return $this->sameMobileAndFixedLinePattern;
    }

    /**
     * @return NumberFormat[]
     */
    public function numberFormats(): array
    {
        return $this->numberFormat;
    }

    /**
     * @return NumberFormat[]
     */
    public function intlNumberFormats(): array
    {
        return $this->intlNumberFormat;
    }

    public function hasLeadingDigits(): bool
    {
        return static::LEADING_DIGITS !== null;
    }

    public function getLeadingDigits(): ?string
    {
        return static::LEADING_DIGITS;
    }

    public function isMobileNumberPortableRegion(): bool
    {
        return $this->mobileNumberPortableRegion;
    }

    public function setInternationalPrefix(string $value): static
    {
        $this->internationalPrefix = $value;
        return $this;
    }
}
