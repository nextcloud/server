<?php

declare(strict_types=1);

namespace libphonenumber;

use Serializable;

/**
 * It is not recommended to create PhoneNumber objects directly, instead you should
 * use PhoneNumberUtil::parse() to parse the number and return a PhoneNumber object
 * @no-named-arguments
 */
class PhoneNumber implements Serializable
{
    /**
     * The country calling code for this number, as defined by the International Telecommunication Union
     * (ITU). For example, this would be 1 for NANPA countries, and 33 for France.
     */
    protected ?int $countryCode = null;
    /**
     * National (significant) Number is defined in International Telecommunication Union (ITU)
     * Recommendation E.164. It is a language/country-neutral representation of a phone number at a
     * country level. For countries which have the concept of an "area code" or "national destination
     * code", this is included in the National (significant) Number. Although the ITU says the maximum
     * length should be 15, we have found longer numbers in some countries e.g. Germany.
     *
     * Note that the National (significant) Number does not contain the National(trunk) prefix.
     */
    protected ?string $nationalNumber = null;
    /**
     * Extension is not standardized in ITU recommendations, except for being defined as a series of
     * numbers with a maximum length of 40 digits. It is defined as a string here to accommodate for the
     * possible use of a leading zero in the extension (organizations have complete freedom to do so,
     * as there is no standard defined). However, only ASCII digits should be stored here.
     */
    protected ?string $extension = null;
    /**
     * In some countries, the national (significant) number starts with one or more "0"s without this
     * being a national prefix or trunk code of some kind. For example, the leading zero in the national
     * (significant) number of an Italian phone number indicates the number is a fixed-line number.
     * There have been plans to migrate fixed-line numbers to start with the digit two since December
     * 2000, but it has not happened yet. See http://en.wikipedia.org/wiki/%2B39 for more details.
     *
     * These fields can be safely ignored (there is no need to set them) for most countries. Some
     * limited number of countries behave like Italy - for these cases, if the leading zero(s) of a
     * number would be retained even when dialling internationally, set this flag to true, and also
     * set the number of leading zeros.
     *
     * Clients who use the parsing functionality of the i18n phone number libraries
     * will have these fields set if necessary automatically.
     */
    protected ?bool $italianLeadingZero = null;
    /**
     * This field is used to store the raw input string containing phone numbers before it was
     * canonicalized by the library. For example, it could be used to store alphanumerical numbers
     * such as "1-800-GOOG-411".
     */
    protected ?string $rawInput = null;
    /**
     * The source from which the country_code is derived. This is not set in the general parsing method,
     * but in the method that parses and keeps raw_input. New fields could be added upon request.
     */
    protected ?CountryCodeSource $countryCodeSource = CountryCodeSource::UNSPECIFIED;
    /**
     * The carrier selection code that is preferred when calling this phone number domestically. This
     * also includes codes that need to be dialed in some countries when calling from landlines to
     * mobiles or vice versa. For example, in Columbia, a "3" needs to be dialed before the phone number
     * itself when calling from a mobile phone to a domestic landline phone and vice versa.
     *
     * Note this is the "preferred" code, which means other codes may work as well.
     */
    protected ?string $preferredDomesticCarrierCode = null;
    /**
     * Whether this phone number has a number of leading zeros set.
     */
    protected bool $hasNumberOfLeadingZeros = false;
    /**
     * The number of leading zeros of this phone number.
     */
    protected int $numberOfLeadingZeros = 1;

    public function clear(): static
    {
        $this->clearCountryCode();
        $this->clearNationalNumber();
        $this->clearExtension();
        $this->clearItalianLeadingZero();
        $this->clearNumberOfLeadingZeros();
        $this->clearRawInput();
        $this->clearCountryCodeSource();
        $this->clearPreferredDomesticCarrierCode();
        return $this;
    }

    public function clearCountryCode(): static
    {
        $this->countryCode = null;
        return $this;
    }

    public function clearNationalNumber(): static
    {
        $this->nationalNumber = null;
        return $this;
    }

    public function clearExtension(): static
    {
        $this->extension = null;
        return $this;
    }

    public function clearItalianLeadingZero(): static
    {
        $this->italianLeadingZero = null;
        return $this;
    }

    public function clearNumberOfLeadingZeros(): static
    {
        $this->hasNumberOfLeadingZeros = false;
        $this->numberOfLeadingZeros = 1;
        return $this;
    }

    public function clearRawInput(): static
    {
        $this->rawInput = null;
        return $this;
    }

    public function clearCountryCodeSource(): static
    {
        $this->countryCodeSource = CountryCodeSource::UNSPECIFIED;
        return $this;
    }

    public function clearPreferredDomesticCarrierCode(): static
    {
        $this->preferredDomesticCarrierCode = null;
        return $this;
    }

    /**
     * Merges the information from another phone number into this phone number.
     */
    public function mergeFrom(PhoneNumber $other): static
    {
        if ($other->hasCountryCode()) {
            $this->setCountryCode($other->getCountryCode());
        }
        if ($other->hasNationalNumber()) {
            $this->setNationalNumber($other->getNationalNumber());
        }
        if ($other->hasExtension()) {
            $this->setExtension($other->getExtension());
        }
        if ($other->hasItalianLeadingZero()) {
            $this->setItalianLeadingZero($other->isItalianLeadingZero());
        }
        if ($other->hasNumberOfLeadingZeros()) {
            $this->setNumberOfLeadingZeros($other->getNumberOfLeadingZeros());
        }
        if ($other->hasRawInput()) {
            $this->setRawInput($other->getRawInput());
        }
        if ($other->hasCountryCodeSource()) {
            $this->setCountryCodeSource($other->getCountryCodeSource());
        }
        if ($other->hasPreferredDomesticCarrierCode()) {
            $this->setPreferredDomesticCarrierCode($other->getPreferredDomesticCarrierCode());
        }
        return $this;
    }

    public function hasCountryCode(): bool
    {
        return $this->countryCode !== null;
    }

    public function getCountryCode(): ?int
    {
        return $this->countryCode;
    }

    public function setCountryCode(int $value): static
    {
        $this->countryCode = $value;
        return $this;
    }

    public function hasNationalNumber(): bool
    {
        return $this->nationalNumber !== null;
    }

    public function getNationalNumber(): ?string
    {
        return $this->nationalNumber;
    }

    public function setNationalNumber(string $value): static
    {
        $this->nationalNumber = $value;
        return $this;
    }

    public function hasExtension(): bool
    {
        return isset($this->extension) && $this->extension !== '';
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(string $value): static
    {
        $this->extension = $value;
        return $this;
    }

    public function hasItalianLeadingZero(): bool
    {
        return isset($this->italianLeadingZero);
    }

    public function setItalianLeadingZero(bool $value): static
    {
        $this->italianLeadingZero = $value;
        return $this;
    }

    /**
     * Returns whether this phone number uses an italian leading zero.
     *
     * @return bool|null True if it uses an italian leading zero, false it it does not, null if not set.
     */
    public function isItalianLeadingZero(): ?bool
    {
        return $this->italianLeadingZero ?? null;
    }

    public function hasNumberOfLeadingZeros(): bool
    {
        return $this->hasNumberOfLeadingZeros;
    }

    public function getNumberOfLeadingZeros(): int
    {
        return $this->numberOfLeadingZeros;
    }

    public function setNumberOfLeadingZeros(int $value): static
    {
        $this->hasNumberOfLeadingZeros = true;
        $this->numberOfLeadingZeros = $value;
        return $this;
    }

    public function hasRawInput(): bool
    {
        return isset($this->rawInput);
    }

    public function getRawInput(): ?string
    {
        return $this->rawInput;
    }

    public function setRawInput(string $value): static
    {
        $this->rawInput = $value;
        return $this;
    }

    public function hasCountryCodeSource(): bool
    {
        return $this->countryCodeSource !== CountryCodeSource::UNSPECIFIED;
    }

    public function getCountryCodeSource(): ?CountryCodeSource
    {
        return $this->countryCodeSource;
    }

    public function setCountryCodeSource(CountryCodeSource $value): static
    {
        $this->countryCodeSource = $value;
        return $this;
    }

    public function hasPreferredDomesticCarrierCode(): bool
    {
        return isset($this->preferredDomesticCarrierCode);
    }

    public function getPreferredDomesticCarrierCode(): ?string
    {
        return $this->preferredDomesticCarrierCode;
    }

    public function setPreferredDomesticCarrierCode(string $value): static
    {
        $this->preferredDomesticCarrierCode = $value;
        return $this;
    }

    /**
     * Returns whether this phone number is equal to another.
     *
     * @param PhoneNumber $other The phone number to compare.
     *
     * @return bool True if the phone numbers are equal, false otherwise.
     */
    public function equals(PhoneNumber $other): bool
    {
        if ($this === $other) {
            return true;
        }

        return $this->countryCode === $other->countryCode
            && $this->nationalNumber === $other->nationalNumber
            && $this->extension === $other->extension
            && $this->italianLeadingZero === $other->italianLeadingZero
            && $this->numberOfLeadingZeros === $other->numberOfLeadingZeros
            && $this->rawInput === $other->rawInput
            && $this->countryCodeSource === $other->countryCodeSource
            && $this->preferredDomesticCarrierCode === $other->preferredDomesticCarrierCode;
    }

    /**
     * Returns a string representation of this phone number.
     */
    public function __toString(): string
    {
        $outputString = 'Country Code: ' . $this->countryCode;
        $outputString .= ' National Number: ' . $this->nationalNumber;
        if ($this->hasItalianLeadingZero()) {
            $outputString .= ' Leading Zero(s): true';
        }
        if ($this->hasNumberOfLeadingZeros()) {
            $outputString .= ' Number of leading zeros: ' . $this->numberOfLeadingZeros;
        }
        if ($this->hasExtension()) {
            $outputString .= ' Extension: ' . $this->extension;
        }
        if ($this->hasCountryCodeSource()) {
            $outputString .= ' Country Code Source: ' . $this->countryCodeSource->name;
        }
        if ($this->hasPreferredDomesticCarrierCode()) {
            $outputString .= ' Preferred Domestic Carrier Code: ' . $this->preferredDomesticCarrierCode;
        }
        return $outputString;
    }

    public function serialize(): ?string
    {
        return serialize($this->__serialize());
    }

    public function __serialize(): array
    {
        return [
            $this->countryCode,
            $this->nationalNumber,
            $this->extension,
            $this->italianLeadingZero,
            $this->numberOfLeadingZeros,
            $this->rawInput,
            $this->countryCodeSource,
            $this->preferredDomesticCarrierCode,
        ];
    }

    public function unserialize($data): void
    {
        $this->__unserialize(unserialize($data, ['allowed_classes' => [__CLASS__]]));
    }

    /**
     * @param array{int,string,string,bool|null,int,string|null,CountryCodeSource|null,string|null} $data
     */
    public function __unserialize(array $data): void
    {
        [
            $this->countryCode,
            $this->nationalNumber,
            $this->extension,
            $this->italianLeadingZero,
            $this->numberOfLeadingZeros,
            $this->rawInput,
            $countryCodeSource,
            $this->preferredDomesticCarrierCode
        ] = $data;

        // BC layer to allow this method to unserialize "old" phonenumbers
        if (is_int($countryCodeSource)) {
            $countryCodeSource = CountryCodeSource::from($countryCodeSource);
        }
        $this->countryCodeSource = $countryCodeSource;

        if ($this->numberOfLeadingZeros > 1) {
            $this->hasNumberOfLeadingZeros = true;
        }
    }
}
