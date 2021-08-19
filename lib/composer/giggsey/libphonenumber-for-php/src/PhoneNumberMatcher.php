<?php

namespace libphonenumber;

use libphonenumber\Leniency\AbstractLeniency;

/**
 * A class that finds and extracts telephone numbers from $text.
 * Instances can be created using PhoneNumberUtil::findNumbers()
 *
 * Vanity numbers (phone numbers using alphabetic digits such as '1-800-SIX-FLAGS' are
 * not found.
 *
 * @package libphonenumber
 */
class PhoneNumberMatcher implements \Iterator
{
    protected static $initialized = false;

    /**
     * The phone number pattern used by $this->find(), similar to
     * PhoneNumberUtil::VALID_PHONE_NUMBER, but with the following differences:
     * <ul>
     *   <li>All captures are limited in order to place an upper bound to the text matched by the
     *       pattern.
     * <ul>
     *   <li>Leading punctuation / plus signs are limited.
     *   <li>Consecutive occurrences of punctuation are limited.
     *   <li>Number of digits is limited.
     * </ul>
     *   <li>No whitespace is allowed at the start or end.
     *   <li>No alpha digits (vanity numbers such as 1-800-SIX-FLAGS) are currently supported.
     * </ul>
     *
     * @var string
     */
    protected static $pattern;

    /**
     * Matches strings that look like publication pages. Example:
     * <pre>Computing Complete Answers to Queries in the Presence of Limited Access Patterns.
     * Chen Li. VLDB J. 12(3): 211-227 (2003).</pre>
     *
     * The string "211-227 (2003)" is not a telephone number.
     *
     * @var string
     */
    protected static $pubPages = "\\d{1,5}-+\\d{1,5}\\s{0,4}\\(\\d{1,4}";

    /**
     * Matches strings that look like dates using "/" as a separator. Examples 3/10/2011, 31/10/2011 or
     * 08/31/95.
     *
     * @var string
     */
    protected static $slashSeparatedDates = "(?:(?:[0-3]?\\d/[01]?\\d)|(?:[01]?\\d/[0-3]?\\d))/(?:[12]\\d)?\\d{2}";

    /**
     * Matches timestamps. Examples: "2012-01-02 08:00". Note that the reg-ex does not include the
     * trailing ":\d\d" -- that is covered by timeStampsSuffix.
     *
     * @var string
     */
    protected static $timeStamps = "[12]\\d{3}[-/]?[01]\\d[-/]?[0-3]\\d +[0-2]\\d$";
    protected static $timeStampsSuffix = ":[0-5]\\d";

    /**
     * Pattern to check that brackets match. Opening brackets should be closed within a phone number.
     * This also checks that there is something inside the brackets. Having no brackets at all is also
     * fine.
     *
     * @var string
     */
    protected static $matchingBrackets;

    /**
     * Patterns used to extract phone numbers from a larger phone-number-like pattern. These are
     * ordered according to specificity. For example, white-space is last since that is frequently
     * used in numbers, not just to separate two numbers. We have separate patterns since we don't
     * want to break up the phone-number-like text on more than one different kind of symbol at one
     * time, although symbols of the same type (e.g. space) can be safely grouped together.
     *
     * Note that if there is a match, we will always check any text found up to the first match as
     * well.
     *
     * @var string[]
     */
    protected static $innerMatches = array();

    /**
     * Punctuation that may be at the start of a phone number - brackets and plus signs.
     *
     * @var string
     */
    protected static $leadClass;

    /**
     * Prefix of the files
     * @var string
     */
    protected static $alternateFormatsFilePrefix;
    const META_DATA_FILE_PREFIX = 'PhoneNumberAlternateFormats';

    protected static function init()
    {
        static::$alternateFormatsFilePrefix = \dirname(__FILE__) . '/data/' . static::META_DATA_FILE_PREFIX;

        static::$innerMatches = array(
            // Breaks on the slash - e.g. "651-234-2345/332-445-1234"
            '/+(.*)',
            // Note that the bracket here is inside the capturing group, since we consider it part of the
            // phone number. Will match a pattern like "(650) 223 3345 (754) 223 3321".
            "(\\([^(]*)",
            // Breaks on a hyphen - e.g. "12345 - 332-445-1234 is my number."
            // We require a space on either side of the hyphen for it to be considered a separator.
            "(?:\\p{Z}-|-\\p{Z})\\p{Z}*(.+)",
            // Various types of wide hyphens. Note we have decided not to enforce a space here, since it's
            // possible that it's supposed to be used to break two numbers without spaces, and we haven't
            // seen many instances of it used within a number.
            "[‒-―－]\\p{Z}*(.+)",
            // Breaks on a full stop - e.g. "12345. 332-445-1234 is my number."
            "\\.+\\p{Z}*([^.]+)",
            // Breaks on space - e.g. "3324451234 8002341234"
            "\\p{Z}+(\\P{Z}+)"
        );

        /*
         * Builds the matchingBrackets and pattern regular expressions. The building blocks exist
         * to make the pattern more easily understood.
         */

        $openingParens = "(\\[\xEF\xBC\x88\xEF\xBC\xBB";
        $closingParens = ")\\]\xEF\xBC\x89\xEF\xBC\xBD";
        $nonParens = '[^' . $openingParens . $closingParens . ']';

        // Limit on the number of pairs of brackets in a phone number.
        $bracketPairLimit = static::limit(0, 3);

        /*
         * An opening bracket at the beginning may not be closed, but subsequent ones should be.  It's
         * also possible that the leading bracket was dropped, so we shouldn't be surprised if we see a
         * closing bracket first. We limit the sets of brackets in a phone number to four.
         */
        static::$matchingBrackets =
            '(?:[' . $openingParens . '])?' . '(?:' . $nonParens . '+' . '[' . $closingParens . '])?'
            . $nonParens . '+'
            . '(?:[' . $openingParens . ']' . $nonParens . '+[' . $closingParens . '])' . $bracketPairLimit
            . $nonParens . '*';

        // Limit on the number of leading (plus) characters.
        $leadLimit = static::limit(0, 2);

        // Limit on the number of consecutive punctuation characters.
        $punctuationLimit = static::limit(0, 4);

        /*
         * The maximum number of digits allowed in a digit-separated block. As we allow all digits in a
         * single block, set high enough to accommodate the entire national number and the international
         * country code
         */
        $digitBlockLimit = PhoneNumberUtil::MAX_LENGTH_FOR_NSN + PhoneNumberUtil::MAX_LENGTH_COUNTRY_CODE;

        /*
         * Limit on the number of blocks separated by the punctuation. Uses digitBlockLimit since some
         * formats use spaces to separate each digit
         */
        $blockLimit = static::limit(0, $digitBlockLimit);

        // A punctuation sequence allowing white space
        $punctuation = '[' . PhoneNumberUtil::VALID_PUNCTUATION . ']' . $punctuationLimit;

        // A digits block without punctuation.
        $digitSequence = "\\p{Nd}" . static::limit(1, $digitBlockLimit);


        $leadClassChars = $openingParens . PhoneNumberUtil::PLUS_CHARS;
        $leadClass = '[' . $leadClassChars . ']';
        static::$leadClass = $leadClass;

        // Init extension patterns from PhoneNumberUtil
        PhoneNumberUtil::initExtnPatterns();

        // Phone number pattern allowing optional punctuation.
        static::$pattern = '(?:' . $leadClass . $punctuation . ')' . $leadLimit
            . $digitSequence . '(?:' . $punctuation . $digitSequence . ')' . $blockLimit
            . '(?:' . PhoneNumberUtil::$EXTN_PATTERNS_FOR_MATCHING . ')?';

        static::$initialized = true;
    }

    /**
     * Helper function to generate regular expression with an upper and lower limit.
     *
     * @param int $lower
     * @param int $upper
     * @return string
     */
    protected static function limit($lower, $upper)
    {
        if (($lower < 0) || ($upper <= 0) || ($upper < $lower)) {
            throw new \InvalidArgumentException();
        }

        return '{' . $lower . ',' . $upper . '}';
    }

    /**
     * The phone number utility.
     * @var PhoneNumberUtil
     */
    protected $phoneUtil;

    /**
     * The text searched for phone numbers.
     * @var string
     */
    protected $text;

    /**
     * The region (country) to assume for phone numbers without an international prefix, possibly
     * null.
     * @var string
     */
    protected $preferredRegion;

    /**
     * The degrees of validation requested.
     * @var AbstractLeniency
     */
    protected $leniency;

    /**
     * The maximum number of retires after matching an invalid number.
     * @var int
     */
    protected $maxTries;

    /**
     * One of:
     *  - NOT_READY
     *  - READY
     *  - DONE
     * @var string
     */
    protected $state = 'NOT_READY';

    /**
     * The last successful match, null unless $this->state = READY
     * @var PhoneNumberMatch
     */
    protected $lastMatch;

    /**
     * The next index to start searching at. Undefined when $this->state = DONE
     * @var int
     */
    protected $searchIndex = 0;

    /**
     * Creates a new instance. See the factory methods in PhoneNumberUtil on how to obtain a new instance.
     *
     *
     * @param PhoneNumberUtil $util The Phone Number Util to use
     * @param string|null $text The text that we will search, null for no text
     * @param string|null $country The country to assume for phone numbers not written in international format.
     *  (with a leading plus, or with the international dialling prefix of the specified region).
     *  May be null, or "ZZ" if only numbers with a leading plus should be considered.
     * @param AbstractLeniency $leniency The leniency to use when evaluating candidate phone numbers
     * @param int $maxTries The maximum number of invalid numbers to try before giving up on the text.
     *  This is to cover degenerate cases where the text has a lot of false positives in it. Must be >= 0
     * @throws \NullPointerException
     * @throws \InvalidArgumentException
     */
    public function __construct(PhoneNumberUtil $util, $text, $country, AbstractLeniency $leniency, $maxTries)
    {
        if ($maxTries < 0) {
            throw new \InvalidArgumentException();
        }

        $this->phoneUtil = $util;
        $this->text = ($text !== null) ? $text : '';
        $this->preferredRegion = $country;
        $this->leniency = $leniency;
        $this->maxTries = $maxTries;

        if (static::$initialized === false) {
            static::init();
        }
    }

    /**
     * Attempts to find the next subsequence in the searched sequence on or after {@code searchIndex}
     * that represents a phone number. Returns the next match, null if none was found.
     *
     * @param int $index The search index to start searching at
     * @return PhoneNumberMatch|null The Phone Number Match found, null if none can be found
     */
    protected function find($index)
    {
        $matcher = new Matcher(static::$pattern, $this->text);
        while (($this->maxTries > 0) && $matcher->find($index)) {
            $start = $matcher->start();
            $cutLength = $matcher->end() - $start;
            $candidate = \mb_substr($this->text, $start, $cutLength);

            // Check for extra numbers at the end.
            // TODO: This is the place to start when trying to support extraction of multiple phone number
            // from split notations (+41 49 123 45 67 / 68).
            $candidate = static::trimAfterFirstMatch(PhoneNumberUtil::$SECOND_NUMBER_START_PATTERN, $candidate);

            $match = $this->extractMatch($candidate, $start);
            if ($match !== null) {
                return $match;
            }

            $index = $start + \mb_strlen($candidate);
            $this->maxTries--;
        }

        return null;
    }

    /**
     * Trims away any characters after the first match of $pattern in $candidate,
     * returning the trimmed version.
     *
     * @param string $pattern
     * @param string $candidate
     * @return string
     */
    protected static function trimAfterFirstMatch($pattern, $candidate)
    {
        $trailingCharsMatcher = new Matcher($pattern, $candidate);
        if ($trailingCharsMatcher->find()) {
            $startChar = $trailingCharsMatcher->start();
            $candidate = \mb_substr($candidate, 0, $startChar);
        }
        return $candidate;
    }

    /**
     * Helper method to determine if a character is a Latin-script letter or not. For our purposes,
     * combining marks should also return true since we assume they have been added to a preceding
     * Latin character.
     *
     * @param string $letter
     * @return bool
     * @internal
     */
    public static function isLatinLetter($letter)
    {
        // Combining marks are a subset of non-spacing-mark.
        if (\preg_match('/\p{L}/u', $letter) !== 1 && \preg_match('/\p{Mn}/u', $letter) !== 1) {
            return false;
        }

        return (\preg_match('/\p{Latin}/u', $letter) === 1)
        || (\preg_match('/\pM+/u', $letter) === 1);
    }

    /**
     * @param string $character
     * @return bool
     */
    protected static function isInvalidPunctuationSymbol($character)
    {
        return $character == '%' || \preg_match('/\p{Sc}/u', $character);
    }

    /**
     * Attempts to extract a match from a $candidate.
     *
     * @param string $candidate The candidate text that might contain a phone number
     * @param int $offset The offset of $candidate within $this->text
     * @return PhoneNumberMatch|null The match found, null if none can be found
     */
    protected function extractMatch($candidate, $offset)
    {
        // Skip a match that is more likely to be a date.
        $dateMatcher = new Matcher(static::$slashSeparatedDates, $candidate);
        if ($dateMatcher->find()) {
            return null;
        }

        // Skip potential time-stamps.
        $timeStampMatcher = new Matcher(static::$timeStamps, $candidate);
        if ($timeStampMatcher->find()) {
            $followingText = \mb_substr($this->text, $offset + \mb_strlen($candidate));
            $timeStampSuffixMatcher = new Matcher(static::$timeStampsSuffix, $followingText);
            if ($timeStampSuffixMatcher->lookingAt()) {
                return null;
            }
        }

        // Try to come up with a valid match given the entire candidate.
        $match = $this->parseAndVerify($candidate, $offset);
        if ($match !== null) {
            return $match;
        }

        // If that failed, try to find an "inner match" - there might be a phone number within this
        // candidate.
        return $this->extractInnerMatch($candidate, $offset);
    }

    /**
     * Attempts to extract a match from $candidate if the whole candidate does not qualify as a
     * match.
     *
     * @param string $candidate The candidate text that might contact a phone number
     * @param int $offset The current offset of $candidate within $this->text
     * @return PhoneNumberMatch|null The match found, null if none can be found
     */
    protected function extractInnerMatch($candidate, $offset)
    {
        foreach (static::$innerMatches as $possibleInnerMatch) {
            $groupMatcher = new Matcher($possibleInnerMatch, $candidate);
            $isFirstMatch = true;

            while ($groupMatcher->find() && $this->maxTries > 0) {
                if ($isFirstMatch) {
                    // We should handle any group before this one too.
                    $group = static::trimAfterFirstMatch(
                        PhoneNumberUtil::$UNWANTED_END_CHAR_PATTERN,
                        \mb_substr($candidate, 0, $groupMatcher->start())
                    );

                    $match = $this->parseAndVerify($group, $offset);
                    if ($match !== null) {
                        return $match;
                    }
                    $this->maxTries--;
                    $isFirstMatch = false;
                }
                $group = static::trimAfterFirstMatch(
                    PhoneNumberUtil::$UNWANTED_END_CHAR_PATTERN,
                    $groupMatcher->group(1)
                );
                $match = $this->parseAndVerify($group, $offset + $groupMatcher->start(1));
                if ($match !== null) {
                    return $match;
                }
                $this->maxTries--;
            }
        }
        return null;
    }

    /**
     * Parses a phone number from the $candidate} using PhoneNumberUtil::parse() and
     * verifies it matches the requested leniency. If parsing and verification succeed, a
     * corresponding PhoneNumberMatch is returned, otherwise this method returns null.
     *
     * @param string $candidate The candidate match
     * @param int $offset The offset of $candidate within $this->text
     * @return PhoneNumberMatch|null The parsed and validated phone number match, or null
     */
    protected function parseAndVerify($candidate, $offset)
    {
        try {
            // Check the candidate doesn't contain any formatting which would indicate that it really
            // isn't a phone number
            $matchingBracketsMatcher = new Matcher(static::$matchingBrackets, $candidate);
            $pubPagesMatcher = new Matcher(static::$pubPages, $candidate);
            if (!$matchingBracketsMatcher->matches() || $pubPagesMatcher->find()) {
                return null;
            }

            // If leniency is set to VALID or stricter, we also want to skip numbers that are surrounded
            // by Latin alphabetic characters, to skip cases like abc8005001234 or 8005001234def.
            if ($this->leniency->compareTo(Leniency::VALID()) >= 0) {
                // If the candidate is not at the start of the text, and does not start with phone-number
                // punctuation, check the previous character.
                $leadClassMatcher = new Matcher(static::$leadClass, $candidate);
                if ($offset > 0 && !$leadClassMatcher->lookingAt()) {
                    $previousChar = \mb_substr($this->text, $offset - 1, 1);
                    // We return null if it is a latin letter or an invalid punctuation symbol.
                    if (static::isInvalidPunctuationSymbol($previousChar) || static::isLatinLetter($previousChar)) {
                        return null;
                    }
                }
                $lastCharIndex = $offset + \mb_strlen($candidate);
                if ($lastCharIndex < \mb_strlen($this->text)) {
                    $nextChar = \mb_substr($this->text, $lastCharIndex, 1);
                    if (static::isInvalidPunctuationSymbol($nextChar) || static::isLatinLetter($nextChar)) {
                        return null;
                    }
                }
            }

            $number = $this->phoneUtil->parseAndKeepRawInput($candidate, $this->preferredRegion);

            if ($this->leniency->verify($number, $candidate, $this->phoneUtil)) {
                // We used parseAndKeepRawInput to create this number, but for now we don't return the extra
                // values parsed. TODO: stop clearing all values here and switch all users over
                // to using rawInput() rather than the rawString() of PhoneNumberMatch
                $number->clearCountryCodeSource();
                $number->clearRawInput();
                $number->clearPreferredDomesticCarrierCode();
                return new PhoneNumberMatch($offset, $candidate, $number);
            }
        } catch (NumberParseException $e) {
            // ignore and continue
        }
        return null;
    }

    /**
     * @param PhoneNumberUtil $util
     * @param PhoneNumber $number
     * @param string $normalizedCandidate
     * @param string[] $formattedNumberGroups
     * @return bool
     */
    public static function allNumberGroupsRemainGrouped(
        PhoneNumberUtil $util,
        PhoneNumber $number,
        $normalizedCandidate,
        $formattedNumberGroups
    ) {
        $fromIndex = 0;
        if ($number->getCountryCodeSource() !== CountryCodeSource::FROM_DEFAULT_COUNTRY) {
            // First skip the country code if the normalized candidate contained it.
            $countryCode = $number->getCountryCode();
            $fromIndex = \mb_strpos($normalizedCandidate, $countryCode) + \mb_strlen($countryCode);
        }

        // Check each group of consecutive digits are not broken into separate groupings in the
        // $normalizedCandidate string.
        $formattedNumberGroupsLength = \count($formattedNumberGroups);
        for ($i = 0; $i < $formattedNumberGroupsLength; $i++) {
            // Fails if the substring of $normalizedCandidate starting from $fromIndex
            // doesn't contain the consecutive digits in $formattedNumberGroups[$i].
            $fromIndex = \mb_strpos($normalizedCandidate, $formattedNumberGroups[$i], $fromIndex);
            if ($fromIndex === false) {
                return false;
            }

            // Moves $fromIndex forward.
            $fromIndex += \mb_strlen($formattedNumberGroups[$i]);
            if ($i === 0 && $fromIndex < \mb_strlen($normalizedCandidate)) {
                // We are at the position right after the NDC. We get the region used for formatting
                // information based on the country code in the phone number, rather than the number itself,
                // as we do not need to distinguish between different countries with the same country
                // calling code and this is faster.
                $region = $util->getRegionCodeForCountryCode($number->getCountryCode());

                if ($util->getNddPrefixForRegion($region, true) !== null
                    && \is_int(\mb_substr($normalizedCandidate, $fromIndex, 1))
                ) {
                    // This means there is no formatting symbol after the NDC. In this case, we only
                    // accept the number if there is no formatting symbol at all in the number, except
                    // for extensions. This is only important for countries with national prefixes.
                    $nationalSignificantNumber = $util->getNationalSignificantNumber($number);
                    return \mb_substr(
                        \mb_substr($normalizedCandidate, $fromIndex - \mb_strlen($formattedNumberGroups[$i])),
                        \mb_strlen($nationalSignificantNumber)
                    ) === $nationalSignificantNumber;
                }
            }
        }
        // The check here makes sure that we haven't mistakenly already used the extension to
        // match the last group of the subscriber number. Note the extension cannot have
        // formatting in-between digits

        if ($number->hasExtension()) {
            return \mb_strpos(\mb_substr($normalizedCandidate, $fromIndex), $number->getExtension()) !== false;
        }

        return true;
    }

    /**
     * @param PhoneNumberUtil $util
     * @param PhoneNumber $number
     * @param string $normalizedCandidate
     * @param string[] $formattedNumberGroups
     * @return bool
     */
    public static function allNumberGroupsAreExactlyPresent(
        PhoneNumberUtil $util,
        PhoneNumber $number,
        $normalizedCandidate,
        $formattedNumberGroups
    ) {
        $candidateGroups = \preg_split(PhoneNumberUtil::NON_DIGITS_PATTERN, $normalizedCandidate);

        // Set this to the last group, skipping it if the number has an extension.
        $candidateNumberGroupIndex = $number->hasExtension() ? \count($candidateGroups) - 2 : \count($candidateGroups) - 1;

        // First we check if the national significant number is formatted as a block.
        // We use contains and not equals, since the national significant number may be present with
        // a prefix such as a national number prefix, or the country code itself.
        if (\count($candidateGroups) == 1
            || \mb_strpos(
                $candidateGroups[$candidateNumberGroupIndex],
                $util->getNationalSignificantNumber($number)
            ) !== false
        ) {
            return true;
        }

        // Starting from the end, go through in reverse, excluding the first group, and check the
        // candidate and number groups are the same.
        for ($formattedNumberGroupIndex = (\count($formattedNumberGroups) - 1);
             $formattedNumberGroupIndex > 0 && $candidateNumberGroupIndex >= 0;
             $formattedNumberGroupIndex--, $candidateNumberGroupIndex--) {
            if ($candidateGroups[$candidateNumberGroupIndex] != $formattedNumberGroups[$formattedNumberGroupIndex]) {
                return false;
            }
        }

        // Now check the first group. There may be a national prefix at the start, so we only check
        // that the candidate group ends with the formatted number group.
        return ($candidateNumberGroupIndex >= 0
            && \mb_substr(
                $candidateGroups[$candidateNumberGroupIndex],
                -\mb_strlen($formattedNumberGroups[0])
            ) == $formattedNumberGroups[0]);
    }

    /**
     * Helper method to get the national-number part of a number, formatted without any national
     * prefix, and return it as a set of digit blocks that would be formatted together.
     *
     * @param PhoneNumberUtil $util
     * @param PhoneNumber $number
     * @param NumberFormat $formattingPattern
     * @return string[]
     */
    protected static function getNationalNumberGroups(
        PhoneNumberUtil $util,
        PhoneNumber $number,
        NumberFormat $formattingPattern = null
    ) {
        if ($formattingPattern === null) {
            // This will be in the format +CC-DG;ext=EXT where DG represents groups of digits.
            $rfc3966Format = $util->format($number, PhoneNumberFormat::RFC3966);
            // We remove the extension part from the formatted string before splitting it into different
            // groups.
            $endIndex = \mb_strpos($rfc3966Format, ';');
            if ($endIndex === false) {
                $endIndex = \mb_strlen($rfc3966Format);
            }

            // The country-code will have a '-' following it.
            $startIndex = \mb_strpos($rfc3966Format, '-') + 1;
            return \explode('-', \mb_substr($rfc3966Format, $startIndex, $endIndex - $startIndex));
        }

        // If a format is provided, we format the NSN only, and split that according to the separator.
        $nationalSignificantNumber = $util->getNationalSignificantNumber($number);
        return \explode('-', $util->formatNsnUsingPattern(
            $nationalSignificantNumber,
            $formattingPattern,
            PhoneNumberFormat::RFC3966
        ));
    }

    /**
     * @param PhoneNumber $number
     * @param string $candidate
     * @param PhoneNumberUtil $util
     * @param \Closure $checker
     * @return bool
     */
    public static function checkNumberGroupingIsValid(
        PhoneNumber $number,
        $candidate,
        PhoneNumberUtil $util,
        \Closure $checker
    ) {
        $normalizedCandidate = PhoneNumberUtil::normalizeDigits($candidate, true /* keep non-digits */);
        $formattedNumberGroups = static::getNationalNumberGroups($util, $number);
        if ($checker($util, $number, $normalizedCandidate, $formattedNumberGroups)) {
            return true;
        }

        // If this didn't pass, see if there are any alternative formats that match, and try them instead.
        $alternateFormats = static::getAlternateFormatsForCountry($number->getCountryCode());

        $nationalSignificantNumber = $util->getNationalSignificantNumber($number);
        if ($alternateFormats !== null) {
            foreach ($alternateFormats->numberFormats() as $alternateFormat) {
                if ($alternateFormat->leadingDigitsPatternSize() > 0) {
                    // There is only one leading digits pattern for alternate formats.
                    $pattern = $alternateFormat->getLeadingDigitsPattern(0);

                    $nationalSignificantNumberMatcher = new Matcher($pattern, $nationalSignificantNumber);
                    if (!$nationalSignificantNumberMatcher->lookingAt()) {
                        // Leading digits don't match; try another one.
                        continue;
                    }
                }

                $formattedNumberGroups = static::getNationalNumberGroups($util, $number, $alternateFormat);
                if ($checker($util, $number, $normalizedCandidate, $formattedNumberGroups)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param PhoneNumber $number
     * @param string $candidate
     * @return bool
     */
    public static function containsMoreThanOneSlashInNationalNumber(PhoneNumber $number, $candidate)
    {
        $firstSlashInBodyIndex = \mb_strpos($candidate, '/');
        if ($firstSlashInBodyIndex === false) {
            // No slashes, this is okay
            return false;
        }

        // Now look for a second one.
        $secondSlashInBodyIndex = \mb_strpos($candidate, '/', $firstSlashInBodyIndex + 1);
        if ($secondSlashInBodyIndex === false) {
            // Only one slash, this is okay
            return false;
        }

        // If the first slash is after the country calling code, this is permitted
        $candidateHasCountryCode = ($number->getCountryCodeSource() === CountryCodeSource::FROM_NUMBER_WITH_PLUS_SIGN
            || $number->getCountryCodeSource() === CountryCodeSource::FROM_NUMBER_WITHOUT_PLUS_SIGN);

        if ($candidateHasCountryCode
            && PhoneNumberUtil::normalizeDigitsOnly(
                \mb_substr($candidate, 0, $firstSlashInBodyIndex)
            ) == $number->getCountryCode()
        ) {
            // Any more slashes and this is illegal
            return (\mb_strpos(\mb_substr($candidate, $secondSlashInBodyIndex + 1), '/') !== false);
        }

        return true;
    }

    /**
     * @param PhoneNumber $number
     * @param string $candidate
     * @param PhoneNumberUtil $util
     * @return bool
     */
    public static function containsOnlyValidXChars(PhoneNumber $number, $candidate, PhoneNumberUtil $util)
    {
        // The characters 'x' and 'X' can be (1) a carrier code, in which case they always precede the
        // national significant number or (2) an extension sign, in which case they always precede the
        // extension number. We assume a carrier code is more than 1 digit, so the first case has to
        // have more than 1 consecutive 'x' or 'X', whereas the second case can only have exactly 1 'x'
        // or 'X'. We ignore the character if it appears as the last character of the string.
        $candidateLength = \mb_strlen($candidate);

        for ($index = 0; $index < $candidateLength - 1; $index++) {
            $charAtIndex = \mb_substr($candidate, $index, 1);
            if ($charAtIndex == 'x' || $charAtIndex == 'X') {
                $charAtNextIndex = \mb_substr($candidate, $index + 1, 1);
                if ($charAtNextIndex == 'x' || $charAtNextIndex == 'X') {
                    // This is the carrier code case, in which the 'X's always precede the national
                    // significant number.
                    $index++;

                    if ($util->isNumberMatch($number, \mb_substr($candidate, $index)) != MatchType::NSN_MATCH) {
                        return false;
                    }
                } elseif (!PhoneNumberUtil::normalizeDigitsOnly(\mb_substr(
                    $candidate,
                    $index
                )) == $number->getExtension()
                ) {
                    // This is the extension sign case, in which the 'x' or 'X' should always precede the
                    // extension number
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param PhoneNumber $number
     * @param PhoneNumberUtil $util
     * @return bool
     */
    public static function isNationalPrefixPresentIfRequired(PhoneNumber $number, PhoneNumberUtil $util)
    {
        // First, check how we deduced the country code. If it was written in international format, then
        // the national prefix is not required.
        if ($number->getCountryCodeSource() !== CountryCodeSource::FROM_DEFAULT_COUNTRY) {
            return true;
        }

        $phoneNumberRegion = $util->getRegionCodeForCountryCode($number->getCountryCode());
        $metadata = $util->getMetadataForRegion($phoneNumberRegion);
        if ($metadata === null) {
            return true;
        }

        // Check if a national prefix should be present when formatting this number.
        $nationalNumber = $util->getNationalSignificantNumber($number);
        $formatRule = $util->chooseFormattingPatternForNumber($metadata->numberFormats(), $nationalNumber);
        // To do this, we check that a national prefix formatting rule was present and that it wasn't
        // just the first-group symbol ($1) with punctuation.
        if (($formatRule !== null) && \mb_strlen($formatRule->getNationalPrefixFormattingRule()) > 0) {
            if ($formatRule->getNationalPrefixOptionalWhenFormatting()) {
                // The national-prefix is optional in these cases, so we don't need to check if it was
                // present.
                return true;
            }

            if (PhoneNumberUtil::formattingRuleHasFirstGroupOnly($formatRule->getNationalPrefixFormattingRule())) {
                // National Prefix not needed for this number.
                return true;
            }

            // Normalize the remainder.
            $rawInputCopy = PhoneNumberUtil::normalizeDigitsOnly($number->getRawInput());
            $rawInput = $rawInputCopy;
            // Check if we found a national prefix and/or carrier code at the start of the raw input, and
            // return the result.
            $carrierCode = null;
            return $util->maybeStripNationalPrefixAndCarrierCode($rawInput, $metadata, $carrierCode);
        }
        return true;
    }


    /**
     * Storage for Alternate Formats
     * @var PhoneMetadata[]
     */
    protected static $callingCodeToAlternateFormatsMap = array();

    /**
     * @param $countryCallingCode
     * @return PhoneMetadata|null
     */
    protected static function getAlternateFormatsForCountry($countryCallingCode)
    {
        $countryCodeSet = AlternateFormatsCountryCodeSet::$alternateFormatsCountryCodeSet;

        if (!\in_array($countryCallingCode, $countryCodeSet)) {
            return null;
        }

        if (!isset(static::$callingCodeToAlternateFormatsMap[$countryCallingCode])) {
            static::loadAlternateFormatsMetadataFromFile($countryCallingCode);
        }

        return static::$callingCodeToAlternateFormatsMap[$countryCallingCode];
    }

    /**
     * @param string $countryCallingCode
     * @throws \Exception
     */
    protected static function loadAlternateFormatsMetadataFromFile($countryCallingCode)
    {
        $fileName = static::$alternateFormatsFilePrefix . '_' . $countryCallingCode . '.php';

        if (!\is_readable($fileName)) {
            throw new \Exception('missing metadata: ' . $fileName);
        }

        $metadataLoader = new DefaultMetadataLoader();
        $data = $metadataLoader->loadMetadata($fileName);
        $metadata = new PhoneMetadata();
        $metadata->fromArray($data);
        static::$callingCodeToAlternateFormatsMap[$countryCallingCode] = $metadata;
    }


    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return PhoneNumberMatch|null
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->lastMatch;
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->lastMatch = $this->find($this->searchIndex);

        if ($this->lastMatch === null) {
            $this->state = 'DONE';
        } else {
            $this->searchIndex = $this->lastMatch->end();
            $this->state = 'READY';
        }

        $this->searchIndex++;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->searchIndex;
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->state === 'READY';
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->searchIndex = 0;
        $this->next();
    }
}
