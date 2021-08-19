<?php

namespace libphonenumber;

/**
 * Class AsYouTypeFormatter
 * A formatter which formats phone numbers as they are entered.
 *
 * An AsYouTypeFormatter instance can be created by invoking PhoneNumberUtil::getAsYouTypeFormatter().
 * After that, digits can be added by invoking inputDigit() on the formatter instance, and the partially
 * formatted phone number will be returned each time a digit is added. clear() can be invokved before
 * formatting a new number.
 */
class AsYouTypeFormatter
{
    /**
     * @var string
     */
    private $currentOutput;
    /**
     * @var string
     */
    private $formattingTemplate;
    /**
     * The pattern from numberFormat that is currently used to create formattingTemplate.
     * @var string
     */
    private $currentFormattingPattern;

    /**
     * @var string
     */
    private $accruedInput;

    /**
     * @var string
     */
    private $accruedInputWithoutFormatting;
    /**
     * This indicated whether AsYouTypeFormatter is currently doing the formatting
     * @var bool
     */
    private $ableToFormat = true;

    /**
     * Set to true when users enter their own formatting. AsYouTypeFormatter will do no formatting at
     * all when this is set to true
     * @var bool
     */
    private $inputHasFormatting = false;

    /**
     * This is set to true when we know the user is entering a full national significant number, since
     * we have either detected a national prefix or an international dialing prefix. When this is
     * true, we will no longer use local formatting patterns.
     * @var bool
     */
    private $isCompleteNumber = false;

    /**
     * @var bool
     */
    private $isExpectingCountryCallingCode = false;

    /**
     * @var PhoneNumberUtil
     */
    private $phoneUtil;

    /**
     * @var string
     */
    private $defaultCountry;

    /**
     * @var PhoneMetadata
     */
    private $defaultMetadata;
    /**
     * @var PhoneMetadata
     */
    private $currentMetadata;

    /**
     * @var NumberFormat[]
     */
    private $possibleFormats = array();

    /**
     * @var int
     */
    private $lastMatchPosition = 0;
    /**
     * The position of a digit upon which inputDigitAndRememberPosition is most recently invoked,
     * as found in the original sequence of characters the user entered.
     * @var int
     */
    private $originalPosition = 0;

    /**
     * The position of a digit upon which inputDigitAndRememberPosition is most recently invoked,
     * as found in accruedInputWithoutFormatting
     * @var int
     */
    private $positionToRemember = 0;

    /**
     * This contains anything that has been entered so far preceding the national significant number,
     * and it is formatted (e.g. with space inserted). For example, this can contain IDD, country code,
     * and/or NDD, etc.
     * @var string
     */
    private $prefixBeforeNationalNumber;

    /**
     * @var bool
     */
    private $shouldAddSpaceAfterNationalPrefix = false;

    /**
     * This contains the national prefix that has been extracted. It contains only digits without
     * formatting
     * @var string
     */
    private $extractedNationalPrefix = '';

    /**
     * @var string
     */
    private $nationalNumber;

    /**
     * @var bool
     */
    private static $initialised = false;
    /**
     * Character used when appropriate to separate a prefix, such as a long NDD or a country
     * calling code, from the national number.
     * @var string
     */
    private static $seperatorBeforeNationalNumber = ' ';
    /**
     * @var PhoneMetadata
     */
    private static $emptyMetadata;

    /**
     * A pattern that is used to determine if a numberFormat under availableFormats is eligible
     * to be used by the AYTF. It is eligible when the format element under numberFormat contains
     * groups of the dollar sign followed by a single digit, separated by valid phone number punctuation.
     * This prevents invalid punctuation (such as the star sign in Israeli star numbers) getting
     * into the output of the AYTF. We require that the first group is present in the output pattern to ensure
     * no data is lost while formatting; when we format as you type, this should always be the case.
     * @var string
     */
    private static $eligibleFormatPattern;

    /**
     * A set of characters that, if found in the national prefix formatting rules, are an indicator
     * to us that we should separate the national prefix from the numbers when formatting.
     * @var string
     */
    private static $nationalPrefixSeparatorsPattern = '[- ]';

    /**
     * This is the minimum length of national number accrued that is required to trigger the
     * formatter. The first element of the leadingDigitsPattern of each numberFormat contains
     * a regular expression that matches up to this number of digits.
     * @var int
     */
    private static $minLeadingDigitsLength = 3;

    /**
     * The digits that have not been entered yet will be represented by a \u2008, the punctuation
     * space.
     * @var string
     */
    private static $digitPattern = "\xE2\x80\x88";


    private static function init()
    {
        if (self::$initialised === false) {
            self::$initialised = true;

            self::$emptyMetadata = new PhoneMetadata();
            self::$emptyMetadata->setInternationalPrefix('NA');

            self::$eligibleFormatPattern = '[' . PhoneNumberUtil::VALID_PUNCTUATION . ']*'
                . "\\$1" . "[" . PhoneNumberUtil::VALID_PUNCTUATION . "]*(\\$\\d"
                . "[" . PhoneNumberUtil::VALID_PUNCTUATION . "]*)*";
        }
    }

    /**
     * Constructs as as-you-type formatter. Should be obtained from PhoneNumberUtil->getAsYouTypeFormatter()
     * @param string $regionCode The country/region where the phone number is being entered
     */
    public function __construct($regionCode)
    {
        self::init();

        $this->phoneUtil = PhoneNumberUtil::getInstance();

        $this->defaultCountry = $regionCode;
        $this->currentMetadata = $this->getMetadataForRegion($this->defaultCountry);
        $this->defaultMetadata = $this->currentMetadata;
    }

    /**
     * The metadata needed by this class is the same for all regions sharing the same country calling
     * code. Therefore, we return the metadata for the 'main' region for this country calling code.
     * @param string $regionCode
     * @return PhoneMetadata
     */
    private function getMetadataForRegion($regionCode)
    {
        $countryCallingCode = $this->phoneUtil->getCountryCodeForRegion($regionCode);
        $mainCountry = $this->phoneUtil->getRegionCodeForCountryCode($countryCallingCode);
        $metadata = $this->phoneUtil->getMetadataForRegion($mainCountry);
        if ($metadata !== null) {
            return $metadata;
        }
        // Set to a default instance of teh metadata. This allows us to function with an incorrect
        // region code, even if the formatting only works for numbers specified with "+".
        return self::$emptyMetadata;
    }

    /**
     * Returns true if a new template is created as opposed to reusing the existing template.
     * @return bool
     */
    private function maybeCreateNewTemplate()
    {
        // When there are multiple available formats, the formatter uses the first format where a
        // formatting template could be created.
        foreach ($this->possibleFormats as $key => $numberFormat) {
            $pattern = $numberFormat->getPattern();
            if ($this->currentFormattingPattern == $pattern) {
                return false;
            }
            if ($this->createFormattingTemplate($numberFormat)) {
                $this->currentFormattingPattern = $pattern;
                $nationalPrefixSeparatorsMatcher = new Matcher(
                    self::$nationalPrefixSeparatorsPattern,
                    $numberFormat->getNationalPrefixFormattingRule()
                );
                $this->shouldAddSpaceAfterNationalPrefix = $nationalPrefixSeparatorsMatcher->find();
                // With a new formatting template, the matched position using the old template
                // needs to be reset.
                $this->lastMatchPosition = 0;
                return true;
            }

            // Remove the current number format from $this->possibleFormats
            unset($this->possibleFormats[$key]);
        }
        $this->ableToFormat = false;
        return false;
    }

    /**
     * @param string $leadingDigits
     */
    private function getAvailableFormats($leadingDigits)
    {
        // First decide whether we should use international or national number rules.
        $isInternationalNumber = $this->isCompleteNumber && $this->extractedNationalPrefix === '';

        $formatList = ($isInternationalNumber && $this->currentMetadata->intlNumberFormatSize() > 0)
            ? $this->currentMetadata->intlNumberFormats()
            : $this->currentMetadata->numberFormats();

        foreach ($formatList as $format) {
            // Discard a few formats that we know are not relevant based on the presence of the national
            // prefix.
            if ($this->extractedNationalPrefix !== ''
                && PhoneNumberUtil::formattingRuleHasFirstGroupOnly(
                    $format->getNationalPrefixFormattingRule()
                )
                && !$format->getNationalPrefixOptionalWhenFormatting()
                && !$format->hasDomesticCarrierCodeFormattingRule()) {
                // If it is a national number that had a national prefix, any rules that aren't valid with a
                // national prefix should be excluded. A rule that has a carrier-code formatting rule is
                // kept since the national prefix might actually be an extracted carrier code - we don't
                // distinguish between these when extracting it in the AYTF.
                continue;
            }

            if ($this->extractedNationalPrefix === ''
                && !$this->isCompleteNumber
                && !PhoneNumberUtil::formattingRuleHasFirstGroupOnly(
                    $format->getNationalPrefixFormattingRule()
                )
                && !$format->getNationalPrefixOptionalWhenFormatting()) {
                // This number was entered without a national prefix, and this formatting rule requires one,
                // so we discard it.
                continue;
            }

            $eligibleFormatMatcher = new Matcher(self::$eligibleFormatPattern, $format->getFormat());

            if ($eligibleFormatMatcher->matches()) {
                $this->possibleFormats[] = $format;
            }
        }
        $this->narrowDownPossibleFormats($leadingDigits);
    }

    /**
     * @param $leadingDigits
     */
    private function narrowDownPossibleFormats($leadingDigits)
    {
        $indexOfLeadingDigitsPattern = \mb_strlen($leadingDigits) - self::$minLeadingDigitsLength;

        foreach ($this->possibleFormats as $key => $format) {
            if ($format->leadingDigitsPatternSize() === 0) {
                // Keep everything that isn't restricted by leading digits.
                continue;
            }
            $lastLeadingDigitsPattern = \min($indexOfLeadingDigitsPattern, $format->leadingDigitsPatternSize() - 1);
            $leadingDigitsPattern = $format->getLeadingDigitsPattern($lastLeadingDigitsPattern);
            $m = new Matcher($leadingDigitsPattern, $leadingDigits);
            if (!$m->lookingAt()) {
                unset($this->possibleFormats[$key]);
            }
        }
    }

    /**
     * @param NumberFormat $format
     * @return bool
     */
    private function createFormattingTemplate(NumberFormat $format)
    {
        $numberPattern = $format->getPattern();

        $this->formattingTemplate = '';
        $tempTemplate = $this->getFormattingTemplate($numberPattern, $format->getFormat());
        if ($tempTemplate !== '') {
            $this->formattingTemplate .= $tempTemplate;
            return true;
        }
        return false;
    }

    /**
     * Gets a formatting template which can be used to efficiently format a partial number where
     * digits are added one by one.
     * @param string $numberPattern
     * @param string $numberFormat
     * @return string
     */
    private function getFormattingTemplate($numberPattern, $numberFormat)
    {
        // Creates a phone number consisting only of the digit 9 that matches the
        // numberPattern by applying the pattern to the longestPhoneNumber string.
        $longestPhoneNumber = '999999999999999';
        $m = new Matcher($numberPattern, $longestPhoneNumber);
        $m->find();
        $aPhoneNumber = $m->group();
        // No formatting template can be created if the number of digits entered entered so far
        // is longer than the maximum the current formatting rule can accommodate.
        if (\mb_strlen($aPhoneNumber) < \mb_strlen($this->nationalNumber)) {
            return '';
        }
        // Formats the number according to $numberFormat
        $template = \preg_replace('/' . $numberPattern . '/' . PhoneNumberUtil::REGEX_FLAGS, $numberFormat, $aPhoneNumber);
        // Replaces each digit with character self::$digitPlattern
        $template = \preg_replace('/9/', self::$digitPattern, $template);
        return $template;
    }

    /**
     * Clears the internal state of the formatter, so it can be reused.
     */
    public function clear()
    {
        $this->currentOutput = '';
        $this->accruedInput = '';
        $this->accruedInputWithoutFormatting = '';
        $this->formattingTemplate = '';
        $this->lastMatchPosition = 0;
        $this->currentFormattingPattern = '';
        $this->prefixBeforeNationalNumber = '';
        $this->extractedNationalPrefix = '';
        $this->nationalNumber = '';
        $this->ableToFormat = true;
        $this->inputHasFormatting = false;
        $this->positionToRemember = 0;
        $this->originalPosition = 0;
        $this->isCompleteNumber = false;
        $this->isExpectingCountryCallingCode = false;
        $this->possibleFormats = array();
        $this->shouldAddSpaceAfterNationalPrefix = false;
        if ($this->currentMetadata !== $this->defaultMetadata) {
            $this->currentMetadata = $this->getMetadataForRegion($this->defaultCountry);
        }
    }

    /**
     * Formats a phone number on-the-fly as each digit is entered.
     *
     * @param string $nextChar the most recently entered digit of a phone number. Formatting characters
     *  are allowed, but as soon as they are encountered this method foramts the number as entered
     *  and not "as you type" anymore. Full width digits and Arabic-indic digits are allowed, and will
     *  be shown as they are.
     * @return string The partially formatted phone number
     */
    public function inputDigit($nextChar)
    {
        $this->currentOutput = $this->inputDigitWithOptionToRememberPosition($nextChar, false);
        return $this->currentOutput;
    }

    /**
     * Same as $this->inputDigit(), but remembers the position where $nextChar is inserted, so
     * that is can be retrieved later by using $this->getRememberedPosition(). The remembered
     * position will be automatically adjusted if additional formatting characters are later
     * inserted/removed in front of $nextChar
     * @param string $nextChar
     * @return string
     */
    public function inputDigitAndRememberPosition($nextChar)
    {
        $this->currentOutput = $this->inputDigitWithOptionToRememberPosition($nextChar, true);
        return $this->currentOutput;
    }

    /**
     * @param string $nextChar
     * @param bool $rememberPosition
     * @return string
     */
    private function inputDigitWithOptionToRememberPosition($nextChar, $rememberPosition)
    {
        $this->accruedInput .= $nextChar;
        if ($rememberPosition) {
            $this->originalPosition = \mb_strlen($this->accruedInput);
        }
        // We do formatting on-the-fly only when each character entered is either a digit, or a plus
        // sign (accepted at the start of the number only).
        if (!$this->isDigitOrLeadingPlusSign($nextChar)) {
            $this->ableToFormat = false;
            $this->inputHasFormatting = true;
        } else {
            $nextChar = $this->normalizeAndAccrueDigitsAndPlusSign($nextChar, $rememberPosition);
        }
        if (!$this->ableToFormat) {
            // When we are unable to format because of reasons other than that formatting chars have been
            // entered, it can be due to really long IDDs or NDDs. If that is the case, we might be able
            // to do formatting again after extracting them.
            if ($this->inputHasFormatting) {
                return $this->accruedInput;
            }

            if ($this->attemptToExtractIdd()) {
                if ($this->attemptToExtractCountryCallingCode()) {
                    return $this->attemptToChoosePatternWithPrefixExtracted();
                }
            } elseif ($this->ableToExtractLongerNdd()) {
                // Add an additional space to separate long NDD and national significant number for
                // readability. We don't set shouldAddSpaceAfterNationalPrefix to true, since we don't want
                // this to change later when we choose formatting templates.
                $this->prefixBeforeNationalNumber .= self::$seperatorBeforeNationalNumber;
                return $this->attemptToChoosePatternWithPrefixExtracted();
            }
            return $this->accruedInput;
        }

        // We start to attempt to format only when at least MIN_LEADING_DIGITS_LENGTH digits (the plus
        // sign is counted as a digit as well for this purpose) have been entered.
        switch (\mb_strlen($this->accruedInputWithoutFormatting)) {
            case 0:
            case 1:
            case 2:
                return $this->accruedInput;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 3:
                if ($this->attemptToExtractIdd()) {
                    $this->isExpectingCountryCallingCode = true;
                } else {
                    // No IDD or plus sign is found, might be entering in national format.
                    $this->extractedNationalPrefix = $this->removeNationalPrefixFromNationalNumber();
                    return $this->attemptToChooseFormattingPattern();
                }
            // fall through
            // no break
            default:
                if ($this->isExpectingCountryCallingCode) {
                    if ($this->attemptToExtractCountryCallingCode()) {
                        $this->isExpectingCountryCallingCode = false;
                    }
                    return $this->prefixBeforeNationalNumber . $this->nationalNumber;
                }
                if (\count($this->possibleFormats) > 0) {
                    // The formatting patterns are already chosen.
                    $tempNationalNumber = $this->inputDigitHelper($nextChar);
                    // See if the accrued digits can be formatted properly already. If not, use the results
                    // from inputDigitHelper, which does formatting based on the formatting pattern chosen.
                    $formattedNumber = $this->attemptToFormatAccruedDigits();
                    if (\mb_strlen($formattedNumber) > 0) {
                        return $formattedNumber;
                    }
                    $this->narrowDownPossibleFormats($this->nationalNumber);
                    if ($this->maybeCreateNewTemplate()) {
                        return $this->inputAccruedNationalNumber();
                    }

                    return $this->ableToFormat
                        ? $this->appendNationalNumber($tempNationalNumber)
                        : $this->accruedInput;
                }

                return $this->attemptToChooseFormattingPattern();
        }
    }

    /**
     * @return string
     */
    private function attemptToChoosePatternWithPrefixExtracted()
    {
        $this->ableToFormat = true;
        $this->isExpectingCountryCallingCode = false;
        $this->possibleFormats = array();
        $this->lastMatchPosition = 0;
        $this->formattingTemplate = '';
        $this->currentFormattingPattern = '';
        return $this->attemptToChooseFormattingPattern();
    }

    /**
     * @return string
     * @internal
     */
    public function getExtractedNationalPrefix()
    {
        return $this->extractedNationalPrefix;
    }

    /**
     * Some national prefixes are a substring of others. If extracting the shorter NDD doesn't result
     * in a number we can format, we try to see if we can extract a longer version here.
     * @return bool
     */
    private function ableToExtractLongerNdd()
    {
        if (\mb_strlen($this->extractedNationalPrefix) > 0) {
            // Put the extracted NDD back to the national number before attempting to extract a new NDD.
            $this->nationalNumber = $this->extractedNationalPrefix . $this->nationalNumber;
            // Remove the previously extracted NDD from prefixBeforeNationalNumber. We cannot simply set
            // it to empty string because people sometimes incorrectly enter national prefix after the
            // country code, e.g. +44 (0)20-1234-5678.
            $indexOfPreviousNdd = \mb_strrpos($this->prefixBeforeNationalNumber, $this->extractedNationalPrefix);
            $this->prefixBeforeNationalNumber = \mb_substr(\str_pad($this->prefixBeforeNationalNumber, $indexOfPreviousNdd), 0, $indexOfPreviousNdd);
        }
        return ($this->extractedNationalPrefix !== $this->removeNationalPrefixFromNationalNumber());
    }

    /**
     * @param string $nextChar
     * @return bool
     */
    private function isDigitOrLeadingPlusSign($nextChar)
    {
        $plusCharsMatcher = new Matcher(PhoneNumberUtil::$PLUS_CHARS_PATTERN, $nextChar);

        return \preg_match('/' . PhoneNumberUtil::DIGITS . '/' . PhoneNumberUtil::REGEX_FLAGS, $nextChar)
            || (\mb_strlen($this->accruedInput) === 1 &&
                $plusCharsMatcher->matches());
    }

    /**
     * Checks to see if there is an exact pattern match for these digits. If so, we should use this
     * instead of any other formatting template whose leadingDigitsPattern also matches the input.
     * @return string
     */
    public function attemptToFormatAccruedDigits()
    {
        foreach ($this->possibleFormats as $numberFormat) {
            $m = new Matcher($numberFormat->getPattern(), $this->nationalNumber);
            if ($m->matches()) {
                $nationalPrefixSeparatorsMatcher = new Matcher(self::$nationalPrefixSeparatorsPattern, $numberFormat->getNationalPrefixFormattingRule());
                $this->shouldAddSpaceAfterNationalPrefix = $nationalPrefixSeparatorsMatcher->find();
                $formattedNumber = $m->replaceAll($numberFormat->getFormat());
                // Check that we did not remove nor add any extra digits when we matched
                // this formatting pattern. This usually happens after we entered the last
                // digit during AYTF. Eg: In case of MX, we swallow mobile token (1) when
                // formatted but AYTF should retain all the number entered and not change
                // in order to match a format (of same leading digits and length) display
                // in that way.
                $fullOutput = $this->appendNationalNumber($formattedNumber);
                $formattedNumberDigitsOnly = PhoneNumberUtil::normalizeDiallableCharsOnly($fullOutput);

                if ($formattedNumberDigitsOnly === $this->accruedInputWithoutFormatting) {
                    // If it's the same (i.e entered number and format is same), then it's
                    // safe to return this in formatted number as nothing is lost / added.
                    return $fullOutput;
                }
            }
        }
        return '';
    }

    /**
     * returns the current position in the partially formatted phone number of the character which was
     * previously passed in as a parameter of $this->inputDigitAndRememberPosition().
     * @return int
     */
    public function getRememberedPosition()
    {
        if (!$this->ableToFormat) {
            return $this->originalPosition;
        }

        $accruedInputIndex = 0;
        $currentOutputIndex = 0;
        $currentOutputLength = \mb_strlen($this->currentOutput);
        while ($accruedInputIndex < $this->positionToRemember && $currentOutputIndex < $currentOutputLength) {
            if (\mb_substr($this->accruedInputWithoutFormatting, $accruedInputIndex, 1) == \mb_substr($this->currentOutput, $currentOutputIndex, 1)) {
                $accruedInputIndex++;
            }
            $currentOutputIndex++;
        }
        return $currentOutputIndex;
    }

    /**
     * Combines the national number with any prefix (IDD/+ and country code or national prefix) that
     * was collected. A space will be inserted between them if the current formatting template
     * indicates this to be suitable.
     * @param string $nationalNumber
     * @return string
     */
    private function appendNationalNumber($nationalNumber)
    {
        $prefixBeforeNationalNumberLength = \mb_strlen($this->prefixBeforeNationalNumber);
        if ($this->shouldAddSpaceAfterNationalPrefix && $prefixBeforeNationalNumberLength > 0
            && \mb_substr($this->prefixBeforeNationalNumber, $prefixBeforeNationalNumberLength - 1, 1)
            != self::$seperatorBeforeNationalNumber
        ) {
            // We want to add a space after the national prefix if the national prefix formatting rule
            // indicates that this would normally be done, with the exception of the case where we already
            // appended a space because the NDD was surprisingly long.
            return $this->prefixBeforeNationalNumber . self::$seperatorBeforeNationalNumber . $nationalNumber;
        }

        return $this->prefixBeforeNationalNumber . $nationalNumber;
    }

    /**
     * Attempts to set the formatting template and returns a string which contains the formatted
     * version of the digits entered so far.
     * @return string
     */
    private function attemptToChooseFormattingPattern()
    {
        // We start to attempt to format only when at least MIN_LEADING_DIGITS_LENGTH digits of national
        // number (excluding national prefix) have been entered.
        if (\mb_strlen($this->nationalNumber) >= self::$minLeadingDigitsLength) {
            $this->getAvailableFormats($this->nationalNumber);
            // See if the accrued digits can be formatted properly already.
            $formattedNumber = $this->attemptToFormatAccruedDigits();
            if (\mb_strlen($formattedNumber) > 0) {
                return $formattedNumber;
            }
            return $this->maybeCreateNewTemplate() ? $this->inputAccruedNationalNumber() : $this->accruedInput;
        }

        return $this->appendNationalNumber($this->nationalNumber);
    }

    /**
     * Invokes inputDigitHelper on each digit of the national number accrued, and returns a formatted
     * string in the end
     * @return string
     */
    private function inputAccruedNationalNumber()
    {
        $lengthOfNationalNumber = \mb_strlen($this->nationalNumber);
        if ($lengthOfNationalNumber > 0) {
            $tempNationalNumber = '';
            for ($i = 0; $i < $lengthOfNationalNumber; $i++) {
                $tempNationalNumber = $this->inputDigitHelper(\mb_substr($this->nationalNumber, $i, 1));
            }
            return $this->ableToFormat ? $this->appendNationalNumber($tempNationalNumber) : $this->accruedInput;
        }

        return $this->prefixBeforeNationalNumber;
    }

    /**
     * Returns true if the current country is a NANPA country and the national number beings with
     * the national prefix
     * @return bool
     */
    private function isNanpaNumberWithNationalPrefix()
    {
        // For NANPA numbers beginning with 1[2-9], treat the 1 as the national prefix. The reason is
        // that national significant numbers in NANPA always start with [2-9] after the national prefix.
        // Numbers beginning with 1[01] can only be short/emergency numbers, which don't need the
        // national prefix.
        return ($this->currentMetadata->getCountryCode() == 1) && (\mb_substr($this->nationalNumber, 0, 1) == '1')
            && (\mb_substr($this->nationalNumber, 1, 1) != '0') && (\mb_substr($this->nationalNumber, 1, 1) != '1');
    }

    /**
     * Returns the national prefix extracted, or an empty string if it is not present.
     * @return string
     */
    private function removeNationalPrefixFromNationalNumber()
    {
        $startOfNationalNumber = 0;
        if ($this->isNanpaNumberWithNationalPrefix()) {
            $startOfNationalNumber = 1;
            $this->prefixBeforeNationalNumber .= '1' . self::$seperatorBeforeNationalNumber;
            $this->isCompleteNumber = true;
        } elseif ($this->currentMetadata->hasNationalPrefixForParsing()) {
            $m = new Matcher($this->currentMetadata->getNationalPrefixForParsing(), $this->nationalNumber);
            // Since some national prefix patterns are entirely optional, check that a national prefix
            // could actually be extracted.
            if ($m->lookingAt() && $m->end() > 0) {
                // When the national prefix is detected, we use international formatting rules instead of
                // national ones, because national formatting rules could contain local formatting rules
                // for numbers entered without area code.
                $this->isCompleteNumber = true;
                $startOfNationalNumber = $m->end();
                $this->prefixBeforeNationalNumber .= \mb_substr($this->nationalNumber, 0, $startOfNationalNumber);
            }
        }
        $nationalPrefix = \mb_substr($this->nationalNumber, 0, $startOfNationalNumber);
        $this->nationalNumber = \mb_substr($this->nationalNumber, $startOfNationalNumber);
        return $nationalPrefix;
    }

    /**
     * Extracts IDD and plus sign to $this->prefixBeforeNationalNumber when they are available, and places
     * the remaining input into $this->nationalNumber.
     * @return bool true when $this->accruedInputWithoutFormatting begins with the plus sign or valid IDD
     *  for $this->defaultCountry.
     */
    private function attemptToExtractIdd()
    {
        $internationalPrefix = "\\" . PhoneNumberUtil::PLUS_SIGN . '|' . $this->currentMetadata->getInternationalPrefix();
        $iddMatcher = new Matcher($internationalPrefix, $this->accruedInputWithoutFormatting);

        if ($iddMatcher->lookingAt()) {
            $this->isCompleteNumber = true;
            $startOfCountryCallingCode = $iddMatcher->end();
            $this->nationalNumber = \mb_substr($this->accruedInputWithoutFormatting, $startOfCountryCallingCode);
            $this->prefixBeforeNationalNumber = \mb_substr($this->accruedInputWithoutFormatting, 0, $startOfCountryCallingCode);
            if (\mb_substr($this->accruedInputWithoutFormatting, 0, 1) != PhoneNumberUtil::PLUS_SIGN) {
                $this->prefixBeforeNationalNumber .= self::$seperatorBeforeNationalNumber;
            }
            return true;
        }
        return false;
    }

    /**
     * Extracts the country calling code from the beginning of $this->nationalNumber to
     * $this->prefixBeforeNationalNumber when they are available, and places the remaining input
     * into $this->>nationalNumber.
     * @return bool true when a valid country calling code can be found
     */
    private function attemptToExtractCountryCallingCode()
    {
        if (\mb_strlen($this->nationalNumber) == 0) {
            return false;
        }
        $numberWithoutCountryCallingCode = '';
        $countryCode = $this->phoneUtil->extractCountryCode($this->nationalNumber, $numberWithoutCountryCallingCode);
        if ($countryCode === 0) {
            return false;
        }
        $this->nationalNumber = $numberWithoutCountryCallingCode;
        $newRegionCode = $this->phoneUtil->getRegionCodeForCountryCode($countryCode);
        if (PhoneNumberUtil::REGION_CODE_FOR_NON_GEO_ENTITY == $newRegionCode) {
            $this->currentMetadata = $this->phoneUtil->getMetadataForNonGeographicalRegion($countryCode);
        } elseif ($newRegionCode != $this->defaultCountry) {
            $this->currentMetadata = $this->getMetadataForRegion($newRegionCode);
        }
        $countryCodeString = (string)$countryCode;
        $this->prefixBeforeNationalNumber .= $countryCodeString . self::$seperatorBeforeNationalNumber;
        // When we have successfully extracted the IDD, the previously extracted NDD should be cleared
        // because it is no longer valid.
        $this->extractedNationalPrefix = '';
        return true;
    }

    /**
     * Accrues digits and the plus sign to $this->accruedInputWithoutFormatting for later use. If
     * $nextChar contains a digit in non-ASCII format (e.g. the full-width version of digits), it
     * is first normalized to the ASCII version. The return value is $nextChar itself, or its
     * normalized version, if $nextChar is a digit in non-ASCII format. This method assumes its
     * input is either a digit or the plus sign.
     * @param string $nextChar
     * @param bool $rememberPosition
     * @return string
     */
    private function normalizeAndAccrueDigitsAndPlusSign($nextChar, $rememberPosition)
    {
        if ($nextChar == PhoneNumberUtil::PLUS_SIGN) {
            $normalizedChar = $nextChar;
            $this->accruedInputWithoutFormatting .= $nextChar;
        } else {
            $normalizedChar = PhoneNumberUtil::normalizeDigits($nextChar, false);
            $this->accruedInputWithoutFormatting .= $normalizedChar;
            $this->nationalNumber .= $normalizedChar;
        }
        if ($rememberPosition) {
            $this->positionToRemember = \mb_strlen($this->accruedInputWithoutFormatting);
        }
        return $normalizedChar;
    }

    /**
     * @param string $nextChar
     * @return string
     */
    private function inputDigitHelper($nextChar)
    {
        // Note that formattingTemplate is not guaranteed to have a value, it could be empty, e.g.
        // when the next digit is entered after extracting an IDD or NDD.
        $digitMatcher = new Matcher(self::$digitPattern, $this->formattingTemplate);
        if ($digitMatcher->find($this->lastMatchPosition)) {
            $tempTemplate = $digitMatcher->replaceFirst($nextChar);
            $this->formattingTemplate = $tempTemplate . \mb_substr($this->formattingTemplate, \mb_strlen(
                $tempTemplate,
                'UTF-8'
            ), null, 'UTF-8');
            $this->lastMatchPosition = $digitMatcher->start();
            return \mb_substr($this->formattingTemplate, 0, $this->lastMatchPosition + 1);
        }

        if (\count($this->possibleFormats) === 1) {
            // More digits are entered than we could handle, and there are no other valid patterns to
            // try.
            $this->ableToFormat = false;
        } // else, we just reset the formatting pattern.
        $this->currentFormattingPattern = '';
        return $this->accruedInput;
    }
}
