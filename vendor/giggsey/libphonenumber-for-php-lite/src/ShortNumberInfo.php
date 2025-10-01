<?php

declare(strict_types=1);

/**
 * Methods for getting information about short phone numbers, such as short codes and emergency
 * numbers. Note that most commercial short numbers are not handled here, but by the
 * {@link PhoneNumberUtil}.
 *
 * @author Shaopeng Jia
 * @author David Yonge-Mallo
 * @since 5.8
 */

namespace libphonenumber;

use RuntimeException;

/**
 * @phpstan-consistent-constructor
 * @no-named-arguments
 */
class ShortNumberInfo
{
    protected static ?ShortNumberInfo $instance;
    /**
     * @var array<int,string[]>
     */
    protected array $countryCallingCodeToRegionCodeMap = [];
    protected const REGIONS_WHERE_EMERGENCY_NUMBERS_MUST_BE_EXACT = [
        'BR',
        'CL',
        'NI',
    ];

    protected function __construct(
        protected MatcherAPIInterface $matcherAPI,
        protected MetadataSourceInterface $metadataSource = new MultiFileMetadataSourceImpl(__NAMESPACE__ . '\data\ShortNumberMetadata_'),
    ) {
        // TODO: Create ShortNumberInfo for a given map
        $this->countryCallingCodeToRegionCodeMap = CountryCodeToRegionCodeMap::COUNTRY_CODE_TO_REGION_CODE_MAP;

        // Initialise PhoneNumberUtil to make sure regex's are setup correctly
        PhoneNumberUtil::getInstance();
    }

    /**
     * Returns the singleton instance of ShortNumberInfo
     */
    public static function getInstance(): ShortNumberInfo
    {
        if (!isset(static::$instance)) {
            static::$instance = new self(new RegexBasedMatcher());
        }

        return static::$instance;
    }

    public static function resetInstance(): void
    {
        static::$instance = null;
    }

    /**
     * Returns a list with the region codes that match the specific country calling code. For
     * non-geographical country calling codes, the region code 001 is returned. Also, in the case
     * of no region code being found, an empty list is returned.
     *
     * @return string[]
     */
    protected function getRegionCodesForCountryCode(int $countryCallingCode): array
    {
        return $this->countryCallingCodeToRegionCodeMap[$countryCallingCode] ?? [];
    }

    /**
     * Helper method to check that the country calling code of the number matches the region it's
     * being dialed from.
     */
    protected function regionDialingFromMatchesNumber(PhoneNumber $number, ?string $regionDialingFrom): bool
    {
        if ($regionDialingFrom === null || $regionDialingFrom === '') {
            return false;
        }

        $regionCodes = $this->getRegionCodesForCountryCode($number->getCountryCode());

        return in_array(strtoupper($regionDialingFrom), $regionCodes, true);
    }

    /**
     * @return string[]
     */
    public function getSupportedRegions(): array
    {
        return ShortNumbersRegionCodeSet::SHORT_NUMBERS_REGION_CODE_SET;
    }

    /**
     * Gets a valid short number for the specified region.
     *
     * @param $regionCode String the region for which an example short number is needed
     * @return string a valid short number for the specified region. Returns an empty string when the
     *                metadata does not contain such information.
     */
    public function getExampleShortNumber(string $regionCode): string
    {
        $phoneMetadata = $this->getMetadataForRegion($regionCode);
        if ($phoneMetadata === null) {
            return '';
        }

        /** @var PhoneNumberDesc $desc */
        $desc = $phoneMetadata->getShortCode();
        if ($desc !== null && $desc->hasExampleNumber()) {
            return $desc->getExampleNumber();
        }
        return '';
    }

    public function getMetadataForRegion(string $regionCode): ?PhoneMetadata
    {
        $regionCode = strtoupper($regionCode);

        if (!in_array($regionCode, ShortNumbersRegionCodeSet::SHORT_NUMBERS_REGION_CODE_SET, true)) {
            return null;
        }

        try {
            return $this->metadataSource->getMetadataForRegion($regionCode);
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     *  Gets a valid short number for the specified cost category.
     *
     * @param string $regionCode the region for which an example short number is needed
     * @param ShortNumberCost $cost the cost category of number that is needed
     * @return string a valid short number for the specified region and cost category. Returns an empty string
     *                when the metadata does not contain such information, or the cost is UNKNOWN_COST.
     */
    public function getExampleShortNumberForCost(string $regionCode, ShortNumberCost $cost): string
    {
        $phoneMetadata = $this->getMetadataForRegion($regionCode);
        if ($phoneMetadata === null) {
            return '';
        }

        $desc = null;
        switch ($cost) {
            case ShortNumberCost::TOLL_FREE:
                $desc = $phoneMetadata->getTollFree();
                break;
            case ShortNumberCost::STANDARD_RATE:
                $desc = $phoneMetadata->getStandardRate();
                break;
            case ShortNumberCost::PREMIUM_RATE:
                $desc = $phoneMetadata->getPremiumRate();
                break;
            default:
                // UNKNOWN_COST numbers are computed by the process of elimination from the other cost categories
                break;
        }

        if ($desc !== null && $desc->hasExampleNumber()) {
            return $desc->getExampleNumber();
        }

        return '';
    }

    /**
     * Returns true if the given number, exactly as dialed, might be used to connect to an emergency
     * service in the given region.
     * <p>
     * This method accepts a string, rather than a PhoneNumber, because it needs to distinguish
     * cases such as "+1 911" and "911", where the former may not connect to an emergency service in
     * all cases but the latter would. This method takes into account cases where the number might
     * contain formatting, or might have additional digits appended (when it is okay to do that in
     * the specified region).
     *
     * @param string $number the phone number to test
     * @param string $regionCode the region where the phone number if being dialled
     * @return bool whether the number might be used to connect to an emergency service in the given region
     */
    public function connectsToEmergencyNumber(string $number, string $regionCode): bool
    {
        return $this->matchesEmergencyNumberHelper($number, $regionCode, true /* allows prefix match */);
    }

    protected function matchesEmergencyNumberHelper(string $number, string $regionCode, bool $allowPrefixMatch): bool
    {
        $number = PhoneNumberUtil::extractPossibleNumber($number);
        $matcher = new Matcher(PhoneNumberUtil::PLUS_CHARS_PATTERN, $number);
        if ($matcher->lookingAt()) {
            // Returns false if the number starts with a plus sign. We don't believe dialing the country
            // code before emergency numbers (e.g. +1911) works, but later, if that proves to work, we can
            // add additional logic here to handle it.
            return false;
        }

        $metadata = $this->getMetadataForRegion($regionCode);
        if ($metadata === null || !$metadata->hasEmergency()) {
            return false;
        }

        $normalizedNumber = PhoneNumberUtil::normalizeDigitsOnly($number);
        $emergencyDesc = $metadata->getEmergency();

        $allowPrefixMatchForRegion = (
            $allowPrefixMatch
            && !in_array(strtoupper($regionCode), static::REGIONS_WHERE_EMERGENCY_NUMBERS_MUST_BE_EXACT, true)
        );

        return $this->matcherAPI->matchNationalNumber($normalizedNumber, $emergencyDesc, $allowPrefixMatchForRegion);
    }

    /**
     * Given a valid short number, determines whether it is carrier-specific (however, nothing is
     * implied about its validity). Carrier-specific numbers may connect to a different end-point, or
     * not connect at all, depending on the user's carrier. If it is important that the number is
     * valid, then its validity must first be checked using {@see isValidShortNumber} or
     * {@see isValidShortNumberForRegion}.
     *
     * @param PhoneNumber $number the valid short number to check
     * @return bool whether the short number is carrier-specific, assuming the input was a valid short
     *              number
     */
    public function isCarrierSpecific(PhoneNumber $number): bool
    {
        $regionCodes = $this->getRegionCodesForCountryCode($number->getCountryCode());
        $regionCode = $this->getRegionCodeForShortNumberFromRegionList($number, $regionCodes);
        $nationalNumber = $this->getNationalSignificantNumber($number);
        $phoneMetadata = $this->getMetadataForRegion($regionCode);

        return ($phoneMetadata !== null) && $this->matchesPossibleNumberAndNationalNumber(
            $nationalNumber,
            $phoneMetadata->getCarrierSpecific()
        );
    }

    /**
     * Given a valid short number, determines whether it is carrier-specific when dialed from the
     * given region (however, nothing is implied about its validity). Carrier-specific numbers may
     * connect to a different end-point, or not connect at all, depending on the user's carrier. If
     * it is important that the number is valid, then its validity must first be checked using
     * {@see isValidShortNumber} or {@see isValidShortNumberForRegion}. Returns false if the
     * number doesn't match the region provided.
     * @param PhoneNumber $number The valid short number to check
     * @param string $regionDialingFrom The region from which the number is dialed
     * @return bool Whether the short number is carrier-specific in the provided region, assuming the
     *              input was a valid short number
     */
    public function isCarrierSpecificForRegion(PhoneNumber $number, string $regionDialingFrom): bool
    {
        if (!$this->regionDialingFromMatchesNumber($number, $regionDialingFrom)) {
            return false;
        }

        $nationalNumber = $this->getNationalSignificantNumber($number);
        $phoneMetadata = $this->getMetadataForRegion($regionDialingFrom);

        return ($phoneMetadata !== null)
            && $this->matchesPossibleNumberAndNationalNumber($nationalNumber, $phoneMetadata->getCarrierSpecific());
    }

    /**
     * Given a valid short number, determines whether it is an SMS service (however, nothing is
     * implied about its validity). An SMS service is where the primary or only intended usage is to
     * receive and/or send text messages (SMSs). This includes MMS as MMS numbers downgrade to SMS if
     * the other party isn't MMS-capable. If it is important that the number is valid, then its
     * validity must first be checked using {@see isValidShortNumber} or {@see isValidShortNumberForRegion}.
     * Returns false if the number doesn't match the region provided.
     *
     * @param PhoneNumber $number The valid short number to check
     * @param string $regionDialingFrom The region from which the number is dialed
     * @return bool Whether the short number is an SMS service in the provided region, assuming the input
     *              was a valid short number.
     */
    public function isSmsServiceForRegion(PhoneNumber $number, string $regionDialingFrom): bool
    {
        if (!$this->regionDialingFromMatchesNumber($number, $regionDialingFrom)) {
            return false;
        }

        $phoneMetadata = $this->getMetadataForRegion($regionDialingFrom);

        return ($phoneMetadata !== null)
            && $this->matchesPossibleNumberAndNationalNumber(
                $this->getNationalSignificantNumber($number),
                $phoneMetadata->getSmsServices()
            );
    }

    /**
     * Helper method to get the region code for a given phone number, from a list of possible region
     * codes. If the list contains more than one region, the first region for which the number is
     * valid is returned.
     *
     * @param string[] $regionCodes
     * @return string|null Region Code (or null if none are found)
     */
    protected function getRegionCodeForShortNumberFromRegionList(PhoneNumber $number, array $regionCodes): ?string
    {
        if (count($regionCodes) === 0) {
            return null;
        }

        if (count($regionCodes) === 1) {
            return $regionCodes[0];
        }

        $nationalNumber = $this->getNationalSignificantNumber($number);

        foreach ($regionCodes as $regionCode) {
            $phoneMetadata = $this->getMetadataForRegion($regionCode);
            if ($phoneMetadata !== null
                && $this->matchesPossibleNumberAndNationalNumber($nationalNumber, $phoneMetadata->getShortCode())
            ) {
                // The number is valid for this region.
                return $regionCode;
            }
        }
        return null;
    }

    /**
     * Check whether a short number is a possible number. If a country calling code is shared by
     * multiple regions, this returns true if it's possible in any of them. This provides a more
     * lenient check than {@see isValidShortNumber}. See {@see isPossibleShortNumberForRegion}
     * for details.
     *
     * @param $number PhoneNumber the short number to check
     * @return bool whether the number is a possible short number
     */
    public function isPossibleShortNumber(PhoneNumber $number): bool
    {
        $regionCodes = $this->getRegionCodesForCountryCode($number->getCountryCode());
        $shortNumberLength = strlen($this->getNationalSignificantNumber($number));

        foreach ($regionCodes as $region) {
            $phoneMetadata = $this->getMetadataForRegion($region);

            if ($phoneMetadata === null) {
                continue;
            }

            if (in_array($shortNumberLength, $phoneMetadata->getGeneralDesc()->getPossibleLength(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether a short number is a possible number when dialled from a region, given the number
     * in the form of a string, and the region where the number is dialled from. This provides a more
     * lenient check than {@see isValidShortNumber}.
     *
     * @param PhoneNumber $shortNumber The short number to check
     * @param string $regionDialingFrom Region dialing From
     * @return bool whether the number is a possible short number
     */
    public function isPossibleShortNumberForRegion(PhoneNumber $shortNumber, string $regionDialingFrom): bool
    {
        if (!$this->regionDialingFromMatchesNumber($shortNumber, $regionDialingFrom)) {
            return false;
        }

        $phoneMetadata = $this->getMetadataForRegion($regionDialingFrom);

        if ($phoneMetadata === null) {
            return false;
        }

        $numberLength = strlen($this->getNationalSignificantNumber($shortNumber));
        return in_array($numberLength, $phoneMetadata->getGeneralDesc()->getPossibleLength(), true);
    }

    /**
     * Tests whether a short number matches a valid pattern. If a country calling code is shared by
     * multiple regions, this returns true if it's valid in any of them. Note that this doesn't verify
     * the number is actually in use, which is impossible to tell by just looking at the number
     * itself. See {@see isValidShortNumberForRegion(PhoneNumber, String)} for details.
     *
     * @param $number PhoneNumber the short number for which we want to test the validity
     * @return bool whether the short number matches a valid pattern
     */
    public function isValidShortNumber(PhoneNumber $number): bool
    {
        $regionCodes = $this->getRegionCodesForCountryCode($number->getCountryCode());
        $regionCode = $this->getRegionCodeForShortNumberFromRegionList($number, $regionCodes);
        if ($regionCode !== null && count($regionCodes) > 1) {
            // If a matching region had been found for the phone number from among two or more regions,
            // then we have already implicitly verified its validity for that region.
            return true;
        }

        return $this->isValidShortNumberForRegion($number, $regionCode);
    }

    /**
     * Tests whether a short number matches a valid pattern in a region. Note that this doesn't verify
     * the number is actually in use, which is impossible to tell by just looking at the number
     * itself.
     *
     * @param PhoneNumber $number The Short number for which we want to test the validity
     * @param string|null $regionDialingFrom the region from which the number is dialed
     * @return bool whether the short number matches a valid pattern
     */
    public function isValidShortNumberForRegion(PhoneNumber $number, ?string $regionDialingFrom): bool
    {
        if (!$this->regionDialingFromMatchesNumber($number, $regionDialingFrom)) {
            return false;
        }
        $phoneMetadata = $this->getMetadataForRegion($regionDialingFrom);

        if ($phoneMetadata === null) {
            return false;
        }

        $shortNumber = $this->getNationalSignificantNumber($number);

        $generalDesc = $phoneMetadata->getGeneralDesc();

        if (!$this->matchesPossibleNumberAndNationalNumber($shortNumber, $generalDesc)) {
            return false;
        }

        $shortNumberDesc = $phoneMetadata->getShortCode();

        return $this->matchesPossibleNumberAndNationalNumber($shortNumber, $shortNumberDesc);
    }

    /**
     * Gets the expected cost category of a short number  when dialled from a region (however, nothing is
     * implied about its validity). If it is important that the number is valid, then its validity
     * must first be checked using {@link isValidShortNumberForRegion}. Note that emergency numbers
     * are always considered toll-free.
     * Example usage:
     * <pre>{@code
     * $shortInfo = ShortNumberInfo::getInstance();
     * $shortNumber = PhoneNumberUtil::parse("110", "US);
     * $regionCode = "FR";
     * if ($shortInfo->isValidShortNumberForRegion($shortNumber, $regionCode)) {
     *     $cost = $shortInfo->getExpectedCostForRegion($shortNumber, $regionCode);
     *    // Do something with the cost information here.
     * }}</pre>
     *
     * @param PhoneNumber $number the short number for which we want to know the expected cost category,
     *                            as a string
     * @param string $regionDialingFrom the region from which the number is dialed
     * @return ShortNumberCost the expected cost category for that region of the short number. Returns ShortNumberCost::UNKNOWN_COST if
     *                         the number does not match a cost category. Note that an invalid number may match any cost
     *                         category.
     */
    public function getExpectedCostForRegion(PhoneNumber $number, string $regionDialingFrom): ShortNumberCost
    {
        if (!$this->regionDialingFromMatchesNumber($number, $regionDialingFrom)) {
            return ShortNumberCost::UNKNOWN_COST;
        }
        // Note that regionDialingFrom may be null, in which case phoneMetadata will also be null.
        $phoneMetadata = $this->getMetadataForRegion($regionDialingFrom);
        if ($phoneMetadata === null) {
            return ShortNumberCost::UNKNOWN_COST;
        }

        $shortNumber = $this->getNationalSignificantNumber($number);

        // The possible lengths are not present for a particular sub-type if they match the general
        // description; for this reason, we check the possible lengths against the general description
        // first to allow an early exit if possible.
        if (!in_array(strlen($shortNumber), $phoneMetadata->getGeneralDesc()->getPossibleLength(), true)) {
            return ShortNumberCost::UNKNOWN_COST;
        }

        // The cost categories are tested in order of decreasing expense, since if for some reason the
        // patterns overlap the most expensive matching cost category should be returned.
        if ($this->matchesPossibleNumberAndNationalNumber($shortNumber, $phoneMetadata->getPremiumRate())) {
            return ShortNumberCost::PREMIUM_RATE;
        }

        if ($this->matchesPossibleNumberAndNationalNumber($shortNumber, $phoneMetadata->getStandardRate())) {
            return ShortNumberCost::STANDARD_RATE;
        }

        if ($this->matchesPossibleNumberAndNationalNumber($shortNumber, $phoneMetadata->getTollFree())) {
            return ShortNumberCost::TOLL_FREE;
        }

        if ($this->isEmergencyNumber($shortNumber, $regionDialingFrom)) {
            // Emergency numbers are implicitly toll-free.
            return ShortNumberCost::TOLL_FREE;
        }

        return ShortNumberCost::UNKNOWN_COST;
    }

    /**
     * Gets the expected cost category of a short number (however, nothing is implied about its
     * validity). If the country calling code is unique to a region, this method behaves exactly the
     * same as {@see getExpectedCostForRegion(PhoneNumber, String)}. However, if the country calling
     * code is shared by multiple regions, then it returns the highest cost in the sequence
     * PREMIUM_RATE, UNKNOWN_COST, STANDARD_RATE, TOLL_FREE. The reason for the position of
     * UNKNOWN_COST in this order is that if a number is UNKNOWN_COST in one region but STANDARD_RATE
     * or TOLL_FREE in another, its expected cost cannot be estimated as one of the latter since it
     * might be a PREMIUM_RATE number.
     *
     * <p>
     * For example, if a number is STANDARD_RATE in the US, but TOLL_FREE in Canada, the expected
     * cost returned by this method will be STANDARD_RATE, since the NANPA countries share the same
     * country calling code.
     * </p>
     *
     * Note: If the region from which the number is dialed is known, it is highly preferable to call
     * {@see getExpectedCostForRegion(PhoneNumber, String)} instead.
     *
     * @param PhoneNumber $number the short number for which we want to know the expected cost category
     * @return ShortNumberCost the highest expected cost category of the short number in the region(s) with the given
     *                         country calling code
     */
    public function getExpectedCost(PhoneNumber $number): ShortNumberCost
    {
        $regionCodes = $this->getRegionCodesForCountryCode($number->getCountryCode());

        if ($regionCodes === []) {
            return ShortNumberCost::UNKNOWN_COST;
        }
        if (count($regionCodes) === 1) {
            return $this->getExpectedCostForRegion($number, $regionCodes[0]);
        }
        $cost = ShortNumberCost::TOLL_FREE;
        foreach ($regionCodes as $regionCode) {
            $costForRegion = $this->getExpectedCostForRegion($number, $regionCode);
            switch ($costForRegion) {
                case ShortNumberCost::PREMIUM_RATE:
                    return ShortNumberCost::PREMIUM_RATE;

                case ShortNumberCost::UNKNOWN_COST:
                    $cost = ShortNumberCost::UNKNOWN_COST;
                    break;

                case ShortNumberCost::STANDARD_RATE:
                    if ($cost !== ShortNumberCost::UNKNOWN_COST) {
                        $cost = ShortNumberCost::STANDARD_RATE;
                    }
                    break;
                case ShortNumberCost::TOLL_FREE:
                    // Do nothing
                    break;
            }
        }
        return $cost;
    }

    /**
     * Returns true if the given number exactly matches an emergency service number in the given
     * region.
     * <p>
     * This method takes into account cases where the number might contain formatting, but doesn't
     * allow additional digits to be appended. Note that {@code isEmergencyNumber(number, region)}
     * implies {@code connectsToEmergencyNumber(number, region)}.
     *
     * @param string $number the phone number to test
     * @param string $regionCode the region where the phone number is being dialled
     * @return bool whether the number exactly matches an emergency services number in the given region
     */
    public function isEmergencyNumber(string $number, string $regionCode): bool
    {
        return $this->matchesEmergencyNumberHelper($number, $regionCode, false /* doesn't allow prefix match */);
    }

    /**
     * Gets the national significant number of the a phone number. Note a national significant number
     * doesn't contain a national prefix or any formatting.
     * <p>
     * This is a temporary duplicate of the {@code getNationalSignificantNumber} method from
     * {@code PhoneNumberUtil}. Ultimately a canonical static version should exist in a separate
     * utility class (to prevent {@code ShortNumberInfo} needing to depend on PhoneNumberUtil).
     *
     * @param PhoneNumber $number the phone number for which the national significant number is needed
     * @return string the national significant number of the PhoneNumber object passed in
     */
    protected function getNationalSignificantNumber(PhoneNumber $number): string
    {
        // If leading zero(s) have been set, we prefix this now. Note this is not a national prefix.
        $nationalNumber = '';
        if ($number->isItalianLeadingZero()) {
            $zeros = str_repeat('0', $number->getNumberOfLeadingZeros());
            $nationalNumber .= $zeros;
        }

        $nationalNumber .= $number->getNationalNumber();

        return $nationalNumber;
    }

    /**
     * TODO: Once we have benchmarked ShortnumberInfo, consider if it is worth keeping
     * this performance optimization.
     */
    protected function matchesPossibleNumberAndNationalNumber(string $number, PhoneNumberDesc $numberDesc): bool
    {
        if (count($numberDesc->getPossibleLength()) > 0 && !in_array(strlen($number), $numberDesc->getPossibleLength(), true)) {
            return false;
        }

        return $this->matcherAPI->matchNationalNumber($number, $numberDesc, false);
    }
}
