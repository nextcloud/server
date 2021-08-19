<?php

namespace libphonenumber;

use libphonenumber\Leniency\AbstractLeniency;

/**
 * Utility for international phone numbers. Functionality includes formatting, parsing and
 * validation.
 *
 * <p>If you use this library, and want to be notified about important changes, please sign up to
 * our <a href="http://groups.google.com/group/libphonenumber-discuss/about">mailing list</a>.
 *
 * NOTE: A lot of methods in this class require Region Code strings. These must be provided using
 * CLDR two-letter region-code format. These should be in upper-case. The list of the codes
 * can be found here:
 * http://www.unicode.org/cldr/charts/30/supplemental/territory_information.html
 *
 * @author Shaopeng Jia
 * @see https://github.com/google/libphonenumber
 */
class PhoneNumberUtil
{
    /** Flags to use when compiling regular expressions for phone numbers */
    const REGEX_FLAGS = 'ui'; //Unicode and case insensitive
    // The minimum and maximum length of the national significant number.
    const MIN_LENGTH_FOR_NSN = 2;
    // The ITU says the maximum length should be 15, but we have found longer numbers in Germany.
    const MAX_LENGTH_FOR_NSN = 17;

    // We don't allow input strings for parsing to be longer than 250 chars. This prevents malicious
    // input from overflowing the regular-expression engine.
    const MAX_INPUT_STRING_LENGTH = 250;

    // The maximum length of the country calling code.
    const MAX_LENGTH_COUNTRY_CODE = 3;

    const REGION_CODE_FOR_NON_GEO_ENTITY = '001';
    const META_DATA_FILE_PREFIX = 'PhoneNumberMetadata';
    const TEST_META_DATA_FILE_PREFIX = 'PhoneNumberMetadataForTesting';

    // Region-code for the unknown region.
    const UNKNOWN_REGION = 'ZZ';

    const NANPA_COUNTRY_CODE = 1;
    /*
     * The prefix that needs to be inserted in front of a Colombian landline number when dialed from
     * a mobile number in Colombia.
     */
    const COLOMBIA_MOBILE_TO_FIXED_LINE_PREFIX = '3';
    // The PLUS_SIGN signifies the international prefix.
    const PLUS_SIGN = '+';
    const PLUS_CHARS = '+＋';
    const STAR_SIGN = '*';

    const RFC3966_EXTN_PREFIX = ';ext=';
    const RFC3966_PREFIX = 'tel:';
    const RFC3966_PHONE_CONTEXT = ';phone-context=';
    const RFC3966_ISDN_SUBADDRESS = ';isub=';

    // We use this pattern to check if the phone number has at least three letters in it - if so, then
    // we treat it as a number where some phone-number digits are represented by letters.
    const VALID_ALPHA_PHONE_PATTERN = '(?:.*?[A-Za-z]){3}.*';
    // We accept alpha characters in phone numbers, ASCII only, upper and lower case.
    const VALID_ALPHA = 'A-Za-z';


    // Default extension prefix to use when formatting. This will be put in front of any extension
    // component of the number, after the main national number is formatted. For example, if you wish
    // the default extension formatting to be " extn: 3456", then you should specify " extn: " here
    // as the default extension prefix. This can be overridden by region-specific preferences.
    const DEFAULT_EXTN_PREFIX = ' ext. ';

    // Regular expression of acceptable punctuation found in phone numbers, used to find numbers in
    // text and to decide what is a viable phone number. This excludes diallable characters.
    // This consists of dash characters, white space characters, full stops, slashes,
    // square brackets, parentheses and tildes. It also includes the letter 'x' as that is found as a
    // placeholder for carrier information in some phone numbers. Full-width variants are also
    // present.
    const VALID_PUNCTUATION = "-x\xE2\x80\x90-\xE2\x80\x95\xE2\x88\x92\xE3\x83\xBC\xEF\xBC\x8D-\xEF\xBC\x8F \xC2\xA0\xC2\xAD\xE2\x80\x8B\xE2\x81\xA0\xE3\x80\x80()\xEF\xBC\x88\xEF\xBC\x89\xEF\xBC\xBB\xEF\xBC\xBD.\\[\\]/~\xE2\x81\x93\xE2\x88\xBC";
    const DIGITS = "\\p{Nd}";

    // Pattern that makes it easy to distinguish whether a region has a single international dialing
    // prefix or not. If a region has a single international prefix (e.g. 011 in USA), it will be
    // represented as a string that contains a sequence of ASCII digits, and possible a tilde, which
    // signals waiting for the tone. If there are multiple available international prefixes in a
    // region, they will be represented as a regex string that always contains one or more characters
    // that are not ASCII digits or a tilde.
    const SINGLE_INTERNATIONAL_PREFIX = "[\\d]+(?:[~\xE2\x81\x93\xE2\x88\xBC\xEF\xBD\x9E][\\d]+)?";
    const NON_DIGITS_PATTERN = "(\\D+)";

    // The FIRST_GROUP_PATTERN was originally set to $1 but there are some countries for which the
    // first group is not used in the national pattern (e.g. Argentina) so the $1 group does not match
    // correctly. Therefore, we use \d, so that the first group actually used in the pattern will be
    // matched.
    const FIRST_GROUP_PATTERN = "(\\$\\d)";
    // Constants used in the formatting rules to represent the national prefix, first group and
    // carrier code respectively.
    const NP_STRING = '$NP';
    const FG_STRING = '$FG';
    const CC_STRING = '$CC';

    // A pattern that is used to determine if the national prefix formatting rule has the first group
    // only, i.e, does not start with the national prefix. Note that the pattern explicitly allows
    // for unbalanced parentheses.
    const FIRST_GROUP_ONLY_PREFIX_PATTERN = '\\(?\\$1\\)?';
    public static $PLUS_CHARS_PATTERN;
    protected static $SEPARATOR_PATTERN;
    protected static $CAPTURING_DIGIT_PATTERN;
    protected static $VALID_START_CHAR_PATTERN;
    public static $SECOND_NUMBER_START_PATTERN = '[\\\\/] *x';
    public static $UNWANTED_END_CHAR_PATTERN = "[[\\P{N}&&\\P{L}]&&[^#]]+$";
    protected static $DIALLABLE_CHAR_MAPPINGS = array();
    protected static $CAPTURING_EXTN_DIGITS;

    /**
     * @var PhoneNumberUtil
     */
    protected static $instance;

    /**
     * Only upper-case variants of alpha characters are stored.
     *
     * @var array
     */
    protected static $ALPHA_MAPPINGS = array(
        'A' => '2',
        'B' => '2',
        'C' => '2',
        'D' => '3',
        'E' => '3',
        'F' => '3',
        'G' => '4',
        'H' => '4',
        'I' => '4',
        'J' => '5',
        'K' => '5',
        'L' => '5',
        'M' => '6',
        'N' => '6',
        'O' => '6',
        'P' => '7',
        'Q' => '7',
        'R' => '7',
        'S' => '7',
        'T' => '8',
        'U' => '8',
        'V' => '8',
        'W' => '9',
        'X' => '9',
        'Y' => '9',
        'Z' => '9',
    );

    /**
     * Map of country calling codes that use a mobile token before the area code. One example of when
     * this is relevant is when determining the length of the national destination code, which should
     * be the length of the area code plus the length of the mobile token.
     *
     * @var array
     */
    protected static $MOBILE_TOKEN_MAPPINGS = array();

    /**
     * Set of country codes that have geographically assigned mobile numbers (see GEO_MOBILE_COUNTRIES
     * below) which are not based on *area codes*. For example, in China mobile numbers start with a
     * carrier indicator, and beyond that are geographically assigned: this carrier indicator is not
     * considered to be an area code.
     *
     * @var array
     */
    protected static $GEO_MOBILE_COUNTRIES_WITHOUT_MOBILE_AREA_CODES;

    /**
     * Set of country calling codes that have geographically assigned mobile numbers. This may not be
     * complete; we add calling codes case by case, as we find geographical mobile numbers or hear
     * from user reports. Note that countries like the US, where we can't distinguish between
     * fixed-line or mobile numbers, are not listed here, since we consider FIXED_LINE_OR_MOBILE to be
     * a possibly geographically-related type anyway (like FIXED_LINE).
     *
     * @var array
     */
    protected static $GEO_MOBILE_COUNTRIES;

    /**
     * For performance reasons, amalgamate both into one map.
     *
     * @var array
     */
    protected static $ALPHA_PHONE_MAPPINGS;

    /**
     * Separate map of all symbols that we wish to retain when formatting alpha numbers. This
     * includes digits, ASCII letters and number grouping symbols such as "-" and " ".
     *
     * @var array
     */
    protected static $ALL_PLUS_NUMBER_GROUPING_SYMBOLS;

    /**
     * Simple ASCII digits map used to populate ALPHA_PHONE_MAPPINGS and
     * ALL_PLUS_NUMBER_GROUPING_SYMBOLS.
     *
     * @var array
     */
    protected static $asciiDigitMappings = array(
        '0' => '0',
        '1' => '1',
        '2' => '2',
        '3' => '3',
        '4' => '4',
        '5' => '5',
        '6' => '6',
        '7' => '7',
        '8' => '8',
        '9' => '9',
    );

    /**
     * Regexp of all possible ways to write extensions, for use when parsing. This will be run as a
     * case-insensitive regexp match. Wide character versions are also provided after each ASCII
     * version.
     *
     * @var String
     */
    protected static $EXTN_PATTERNS_FOR_PARSING;
    /**
     * @var string
     * @internal
     */
    public static $EXTN_PATTERNS_FOR_MATCHING;
    protected static $EXTN_PATTERN;
    protected static $VALID_PHONE_NUMBER_PATTERN;
    protected static $MIN_LENGTH_PHONE_NUMBER_PATTERN;
    /**
     *  Regular expression of viable phone numbers. This is location independent. Checks we have at
     * least three leading digits, and only valid punctuation, alpha characters and
     * digits in the phone number. Does not include extension data.
     * The symbol 'x' is allowed here as valid punctuation since it is often used as a placeholder for
     * carrier codes, for example in Brazilian phone numbers. We also allow multiple "+" characters at
     * the start.
     * Corresponds to the following:
     * [digits]{minLengthNsn}|
     * plus_sign*(([punctuation]|[star])*[digits]){3,}([punctuation]|[star]|[digits]|[alpha])*
     *
     * The first reg-ex is to allow short numbers (two digits long) to be parsed if they are entered
     * as "15" etc, but only if there is no punctuation in them. The second expression restricts the
     * number of digits to three or more, but then allows them to be in international form, and to
     * have alpha-characters and punctuation.
     *
     * Note VALID_PUNCTUATION starts with a -, so must be the first in the range.
     *
     * @var string
     */
    protected static $VALID_PHONE_NUMBER;
    protected static $numericCharacters = array(
        "\xef\xbc\x90" => 0,
        "\xef\xbc\x91" => 1,
        "\xef\xbc\x92" => 2,
        "\xef\xbc\x93" => 3,
        "\xef\xbc\x94" => 4,
        "\xef\xbc\x95" => 5,
        "\xef\xbc\x96" => 6,
        "\xef\xbc\x97" => 7,
        "\xef\xbc\x98" => 8,
        "\xef\xbc\x99" => 9,

        "\xd9\xa0" => 0,
        "\xd9\xa1" => 1,
        "\xd9\xa2" => 2,
        "\xd9\xa3" => 3,
        "\xd9\xa4" => 4,
        "\xd9\xa5" => 5,
        "\xd9\xa6" => 6,
        "\xd9\xa7" => 7,
        "\xd9\xa8" => 8,
        "\xd9\xa9" => 9,

        "\xdb\xb0" => 0,
        "\xdb\xb1" => 1,
        "\xdb\xb2" => 2,
        "\xdb\xb3" => 3,
        "\xdb\xb4" => 4,
        "\xdb\xb5" => 5,
        "\xdb\xb6" => 6,
        "\xdb\xb7" => 7,
        "\xdb\xb8" => 8,
        "\xdb\xb9" => 9,

        "\xe1\xa0\x90" => 0,
        "\xe1\xa0\x91" => 1,
        "\xe1\xa0\x92" => 2,
        "\xe1\xa0\x93" => 3,
        "\xe1\xa0\x94" => 4,
        "\xe1\xa0\x95" => 5,
        "\xe1\xa0\x96" => 6,
        "\xe1\xa0\x97" => 7,
        "\xe1\xa0\x98" => 8,
        "\xe1\xa0\x99" => 9,
    );

    /**
     * The set of county calling codes that map to the non-geo entity region ("001").
     *
     * @var array
     */
    protected $countryCodesForNonGeographicalRegion = array();
    /**
     * The set of regions the library supports.
     *
     * @var array
     */
    protected $supportedRegions = array();

    /**
     * A mapping from a country calling code to the region codes which denote the region represented
     * by that country calling code. In the case of multiple regions sharing a calling code, such as
     * the NANPA regions, the one indicated with "isMainCountryForCode" in the metadata should be
     * first.
     *
     * @var array
     */
    protected $countryCallingCodeToRegionCodeMap = array();
    /**
     * The set of regions that share country calling code 1.
     *
     * @var array
     */
    protected $nanpaRegions = array();

    /**
     * @var MetadataSourceInterface
     */
    protected $metadataSource;

    /**
     * @var MatcherAPIInterface
     */
    protected $matcherAPI;

    /**
     * This class implements a singleton, so the only constructor is protected.
     * @param MetadataSourceInterface $metadataSource
     * @param $countryCallingCodeToRegionCodeMap
     */
    protected function __construct(MetadataSourceInterface $metadataSource, $countryCallingCodeToRegionCodeMap)
    {
        $this->metadataSource = $metadataSource;
        $this->countryCallingCodeToRegionCodeMap = $countryCallingCodeToRegionCodeMap;
        $this->init();
        $this->matcherAPI = RegexBasedMatcher::create();
        static::initExtnPatterns();
        static::initExtnPattern();
        static::$PLUS_CHARS_PATTERN = '[' . static::PLUS_CHARS . ']+';
        static::$SEPARATOR_PATTERN = '[' . static::VALID_PUNCTUATION . ']+';
        static::$CAPTURING_DIGIT_PATTERN = '(' . static::DIGITS . ')';
        static::initValidStartCharPattern();
        static::initAlphaPhoneMappings();
        static::initDiallableCharMappings();

        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS = array();
        // Put (lower letter -> upper letter) and (upper letter -> upper letter) mappings.
        foreach (static::$ALPHA_MAPPINGS as $c => $value) {
            static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS[strtolower($c)] = $c;
            static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS[$c] = $c;
        }
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS += static::$asciiDigitMappings;
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS['-'] = '-';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xEF\xBC\x8D"] = '-';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xE2\x80\x90"] = '-';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xE2\x80\x91"] = '-';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xE2\x80\x92"] = '-';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xE2\x80\x93"] = '-';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xE2\x80\x94"] = '-';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xE2\x80\x95"] = '-';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xE2\x88\x92"] = '-';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS['/'] = '/';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xEF\xBC\x8F"] = '/';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS[' '] = ' ';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xE3\x80\x80"] = ' ';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xE2\x81\xA0"] = ' ';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS['.'] = '.';
        static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS["\xEF\xBC\x8E"] = '.';


        static::initValidPhoneNumberPatterns();

        static::$UNWANTED_END_CHAR_PATTERN = '[^' . static::DIGITS . static::VALID_ALPHA . '#]+$';

        static::initMobileTokenMappings();

        static::$GEO_MOBILE_COUNTRIES_WITHOUT_MOBILE_AREA_CODES = array();
        static::$GEO_MOBILE_COUNTRIES_WITHOUT_MOBILE_AREA_CODES[] = 86; // China

        static::$GEO_MOBILE_COUNTRIES = array();
        static::$GEO_MOBILE_COUNTRIES[] = 52; // Mexico
        static::$GEO_MOBILE_COUNTRIES[] = 54; // Argentina
        static::$GEO_MOBILE_COUNTRIES[] = 55; // Brazil
        static::$GEO_MOBILE_COUNTRIES[] = 62; // Indonesia: some prefixes only (fixed CMDA wireless)

        static::$GEO_MOBILE_COUNTRIES = array_merge(static::$GEO_MOBILE_COUNTRIES, static::$GEO_MOBILE_COUNTRIES_WITHOUT_MOBILE_AREA_CODES);
    }

    /**
     * Gets a {@link PhoneNumberUtil} instance to carry out international phone number formatting,
     * parsing or validation. The instance is loaded with phone number metadata for a number of most
     * commonly used regions.
     *
     * <p>The {@link PhoneNumberUtil} is implemented as a singleton. Therefore calling getInstance
     * multiple times will only result in one instance being created.
     *
     * @param string $baseFileLocation
     * @param array|null $countryCallingCodeToRegionCodeMap
     * @param MetadataLoaderInterface|null $metadataLoader
     * @param MetadataSourceInterface|null $metadataSource
     * @return PhoneNumberUtil instance
     */
    public static function getInstance($baseFileLocation = self::META_DATA_FILE_PREFIX, array $countryCallingCodeToRegionCodeMap = null, MetadataLoaderInterface $metadataLoader = null, MetadataSourceInterface $metadataSource = null)
    {
        if (static::$instance === null) {
            if ($countryCallingCodeToRegionCodeMap === null) {
                $countryCallingCodeToRegionCodeMap = CountryCodeToRegionCodeMap::$countryCodeToRegionCodeMap;
            }

            if ($metadataLoader === null) {
                $metadataLoader = new DefaultMetadataLoader();
            }

            if ($metadataSource === null) {
                $metadataSource = new MultiFileMetadataSourceImpl($metadataLoader, __DIR__ . '/data/' . $baseFileLocation);
            }

            static::$instance = new static($metadataSource, $countryCallingCodeToRegionCodeMap);
        }
        return static::$instance;
    }

    protected function init()
    {
        $supportedRegions = array(array());

        foreach ($this->countryCallingCodeToRegionCodeMap as $countryCode => $regionCodes) {
            // We can assume that if the country calling code maps to the non-geo entity region code then
            // that's the only region code it maps to.
            if (count($regionCodes) === 1 && static::REGION_CODE_FOR_NON_GEO_ENTITY === $regionCodes[0]) {
                // This is the subset of all country codes that map to the non-geo entity region code.
                $this->countryCodesForNonGeographicalRegion[] = $countryCode;
            } else {
                // The supported regions set does not include the "001" non-geo entity region code.
                $supportedRegions[] = $regionCodes;
            }
        }

        $this->supportedRegions = call_user_func_array('array_merge', $supportedRegions);


        // If the non-geo entity still got added to the set of supported regions it must be because
        // there are entries that list the non-geo entity alongside normal regions (which is wrong).
        // If we discover this, remove the non-geo entity from the set of supported regions and log.
        $idx_region_code_non_geo_entity = array_search(static::REGION_CODE_FOR_NON_GEO_ENTITY, $this->supportedRegions);
        if ($idx_region_code_non_geo_entity !== false) {
            unset($this->supportedRegions[$idx_region_code_non_geo_entity]);
        }
        $this->nanpaRegions = $this->countryCallingCodeToRegionCodeMap[static::NANPA_COUNTRY_CODE];
    }

    /**
     * @internal
     */
    public static function initExtnPatterns()
    {
        static::$EXTN_PATTERNS_FOR_PARSING = static::createExtnPattern(true);
        static::$EXTN_PATTERNS_FOR_MATCHING = static::createExtnPattern(false);
    }

    /**
     * Helper method for constructing regular expressions for parsing. Creates an expression that
     * captures up to maxLength digits.
     * @param int $maxLength
     * @return string
     */
    private static function extnDigits($maxLength)
    {
        return '(' . self::DIGITS . '{1,' . $maxLength . '})';
    }

    /**
     * Helper initialiser method to create the regular-expression pattern to match extensions.
     * Note that there are currently six capturing groups for the extension itself. If this number is
     * changed, MaybeStripExtension needs to be updated.
     *
     * @param boolean $forParsing
     * @return string
     */
    protected static function createExtnPattern($forParsing)
    {
        // We cap the maximum length of an extension based on the ambiguity of the way the extension is
        // prefixed. As per ITU, the officially allowed length for extensions is actually 40, but we
        // don't support this since we haven't seen real examples and this introduces many false
        // interpretations as the extension labels are not standardized.
        $extLimitAfterExplicitLabel = 20;
        $extLimitAfterLikelyLabel = 15;
        $extLimitAfterAmbiguousChar = 9;
        $extLimitWhenNotSure = 6;



        $possibleSeparatorsBetweenNumberAndExtLabel = "[ \xC2\xA0\\t,]*";
        // Optional full stop (.) or colon, followed by zero or more spaces/tabs/commas.
        $possibleCharsAfterExtLabel = "[:\\.\xEf\xBC\x8E]?[ \xC2\xA0\\t,-]*";
        $optionalExtnSuffix = "#?";

        // Here the extension is called out in more explicit way, i.e mentioning it obvious patterns
        // like "ext.". Canonical-equivalence doesn't seem to be an option with Android java, so we
        // allow two options for representing the accented o - the character itself, and one in the
        // unicode decomposed form with the combining acute accent.
        $explicitExtLabels = "(?:e?xt(?:ensi(?:o\xCC\x81?|\xC3\xB3))?n?|\xEF\xBD\x85?\xEF\xBD\x98\xEF\xBD\x94\xEF\xBD\x8E?|\xD0\xB4\xD0\xBE\xD0\xB1|anexo)";
        // One-character symbols that can be used to indicate an extension, and less commonly used
        // or more ambiguous extension labels.
        $ambiguousExtLabels = "(?:[x\xEF\xBD\x98#\xEF\xBC\x83~\xEF\xBD\x9E]|int|\xEF\xBD\x89\xEF\xBD\x8E\xEF\xBD\x94)";
        // When extension is not separated clearly.
        $ambiguousSeparator = "[- ]+";

        $rfcExtn = static::RFC3966_EXTN_PREFIX . static::extnDigits($extLimitAfterExplicitLabel);
        $explicitExtn = $possibleSeparatorsBetweenNumberAndExtLabel . $explicitExtLabels
            . $possibleCharsAfterExtLabel . static::extnDigits($extLimitAfterExplicitLabel)
            . $optionalExtnSuffix;
        $ambiguousExtn = $possibleSeparatorsBetweenNumberAndExtLabel . $ambiguousExtLabels
            . $possibleCharsAfterExtLabel . static::extnDigits($extLimitAfterAmbiguousChar) . $optionalExtnSuffix;
        $americanStyleExtnWithSuffix = $ambiguousSeparator . static::extnDigits($extLimitWhenNotSure) . "#";

        // The first regular expression covers RFC 3966 format, where the extension is added using
        // ";ext=". The second more generic where extension is mentioned with explicit labels like
        // "ext:". In both the above cases we allow more numbers in extension than any other extension
        // labels. The third one captures when single character extension labels or less commonly used
        // labels are used. In such cases we capture fewer extension digits in order to reduce the
        // chance of falsely interpreting two numbers beside each other as a number + extension. The
        // fourth one covers the special case of American numbers where the extension is written with a
        // hash at the end, such as "- 503#".
        $extensionPattern =
            $rfcExtn . "|"
            . $explicitExtn . "|"
            . $ambiguousExtn . "|"
            . $americanStyleExtnWithSuffix;
        // Additional pattern that is supported when parsing extensions, not when matching.
        if ($forParsing) {
            // This is same as possibleSeparatorsBetweenNumberAndExtLabel, but not matching comma as
            // extension label may have it.
            $possibleSeparatorsNumberExtLabelNoComma = "[ \xC2\xA0\\t]*";
            // ",," is commonly used for auto dialling the extension when connected. First comma is matched
            // through possibleSeparatorsBetweenNumberAndExtLabel, so we do not repeat it here. Semi-colon
            // works in Iphone and Android also to pop up a button with the extension number following.
            $autoDiallingAndExtLabelsFound = "(?:,{2}|;)";

            $autoDiallingExtn = $possibleSeparatorsNumberExtLabelNoComma
                . $autoDiallingAndExtLabelsFound . $possibleCharsAfterExtLabel
                . static::extnDigits($extLimitAfterLikelyLabel) . $optionalExtnSuffix;
            $onlyCommasExtn = $possibleSeparatorsNumberExtLabelNoComma
                . '(?:,)+' . $possibleCharsAfterExtLabel . static::extnDigits($extLimitAfterAmbiguousChar)
                . $optionalExtnSuffix;
            // Here the first pattern is exclusively for extension autodialling formats which are used
            // when dialling and in this case we accept longer extensions. However, the second pattern
            // is more liberal on the number of commas that acts as extension labels, so we have a strict
            // cap on the number of digits in such extensions.
            return $extensionPattern . "|"
                . $autoDiallingExtn . "|"
                . $onlyCommasExtn;
        }
        return $extensionPattern;
    }

    protected static function initExtnPattern()
    {
        static::$EXTN_PATTERN = '/(?:' . static::$EXTN_PATTERNS_FOR_PARSING . ')$/' . static::REGEX_FLAGS;
    }

    protected static function initValidPhoneNumberPatterns()
    {
        static::initExtnPatterns();
        static::$MIN_LENGTH_PHONE_NUMBER_PATTERN = '[' . static::DIGITS . ']{' . static::MIN_LENGTH_FOR_NSN . '}';
        static::$VALID_PHONE_NUMBER = '[' . static::PLUS_CHARS . ']*(?:[' . static::VALID_PUNCTUATION . static::STAR_SIGN . ']*[' . static::DIGITS . ']){3,}[' . static::VALID_PUNCTUATION . static::STAR_SIGN . static::VALID_ALPHA . static::DIGITS . ']*';
        static::$VALID_PHONE_NUMBER_PATTERN = '%^' . static::$MIN_LENGTH_PHONE_NUMBER_PATTERN . '$|^' . static::$VALID_PHONE_NUMBER . '(?:' . static::$EXTN_PATTERNS_FOR_PARSING . ')?$%' . static::REGEX_FLAGS;
    }

    protected static function initAlphaPhoneMappings()
    {
        static::$ALPHA_PHONE_MAPPINGS = static::$ALPHA_MAPPINGS + static::$asciiDigitMappings;
    }

    protected static function initValidStartCharPattern()
    {
        static::$VALID_START_CHAR_PATTERN = '[' . static::PLUS_CHARS . static::DIGITS . ']';
    }

    protected static function initMobileTokenMappings()
    {
        static::$MOBILE_TOKEN_MAPPINGS = array();
        static::$MOBILE_TOKEN_MAPPINGS['54'] = '9';
    }

    protected static function initDiallableCharMappings()
    {
        static::$DIALLABLE_CHAR_MAPPINGS = static::$asciiDigitMappings;
        static::$DIALLABLE_CHAR_MAPPINGS[static::PLUS_SIGN] = static::PLUS_SIGN;
        static::$DIALLABLE_CHAR_MAPPINGS['*'] = '*';
        static::$DIALLABLE_CHAR_MAPPINGS['#'] = '#';
    }

    /**
     * Used for testing purposes only to reset the PhoneNumberUtil singleton to null.
     */
    public static function resetInstance()
    {
        static::$instance = null;
    }

    /**
     * Converts all alpha characters in a number to their respective digits on a keypad, but retains
     * existing formatting.
     *
     * @param string $number
     * @return string
     */
    public static function convertAlphaCharactersInNumber($number)
    {
        if (static::$ALPHA_PHONE_MAPPINGS === null) {
            static::initAlphaPhoneMappings();
        }

        return static::normalizeHelper($number, static::$ALPHA_PHONE_MAPPINGS, false);
    }

    /**
     * Normalizes a string of characters representing a phone number by replacing all characters found
     * in the accompanying map with the values therein, and stripping all other characters if
     * removeNonMatches is true.
     *
     * @param string $number a string of characters representing a phone number
     * @param array $normalizationReplacements a mapping of characters to what they should be replaced by in
     * the normalized version of the phone number.
     * @param bool $removeNonMatches indicates whether characters that are not able to be replaced.
     * should be stripped from the number. If this is false, they will be left unchanged in the number.
     * @return string the normalized string version of the phone number.
     */
    protected static function normalizeHelper($number, array $normalizationReplacements, $removeNonMatches)
    {
        $normalizedNumber = '';
        $strLength = mb_strlen($number, 'UTF-8');
        for ($i = 0; $i < $strLength; $i++) {
            $character = mb_substr($number, $i, 1, 'UTF-8');
            if (isset($normalizationReplacements[mb_strtoupper($character, 'UTF-8')])) {
                $normalizedNumber .= $normalizationReplacements[mb_strtoupper($character, 'UTF-8')];
            } elseif (!$removeNonMatches) {
                $normalizedNumber .= $character;
            }
            // If neither of the above are true, we remove this character.
        }
        return $normalizedNumber;
    }

    /**
     * Helper function to check if the national prefix formatting rule has the first group only, i.e.,
     * does not start with the national prefix.
     *
     * @param string $nationalPrefixFormattingRule
     * @return bool
     */
    public static function formattingRuleHasFirstGroupOnly($nationalPrefixFormattingRule)
    {
        $firstGroupOnlyPrefixPatternMatcher = new Matcher(
            static::FIRST_GROUP_ONLY_PREFIX_PATTERN,
            $nationalPrefixFormattingRule
        );

        return mb_strlen($nationalPrefixFormattingRule) === 0
            || $firstGroupOnlyPrefixPatternMatcher->matches();
    }

    /**
     * Returns all regions the library has metadata for.
     *
     * @return array An unordered array of the two-letter region codes for every geographical region the
     *  library supports
     */
    public function getSupportedRegions()
    {
        return $this->supportedRegions;
    }

    /**
     * Returns all global network calling codes the library has metadata for.
     *
     * @return array An unordered array of the country calling codes for every non-geographical entity
     *  the library supports
     */
    public function getSupportedGlobalNetworkCallingCodes()
    {
        return $this->countryCodesForNonGeographicalRegion;
    }

    /**
     * Returns all country calling codes the library has metadata for, covering both non-geographical
     * entities (global network calling codes) and those used for geographical entities. The could be
     * used to populate a drop-down box of country calling codes for a phone-number widget, for
     * instance.
     *
     * @return array An unordered array of the country calling codes for every geographical and
     *      non-geographical entity the library supports
     */
    public function getSupportedCallingCodes()
    {
        return array_keys($this->countryCallingCodeToRegionCodeMap);
    }

    /**
     * Returns true if there is any possible number data set for a particular PhoneNumberDesc.
     *
     * @param PhoneNumberDesc $desc
     * @return bool
     */
    protected static function descHasPossibleNumberData(PhoneNumberDesc $desc)
    {
        // If this is empty, it means numbers of this type inherit from the "general desc" -> the value
        // '-1' means that no numbers exist for this type.
        $possibleLength = $desc->getPossibleLength();
        return count($possibleLength) != 1 || $possibleLength[0] != -1;
    }

    /**
     * Returns true if there is any data set for a particular PhoneNumberDesc.
     *
     * @param PhoneNumberDesc $desc
     * @return bool
     */
    protected static function descHasData(PhoneNumberDesc $desc)
    {
        // Checking most properties since we don't know what's present, since a custom build may have
        // stripped just one of them (e.g. liteBuild strips exampleNumber). We don't bother checking the
        // possibleLengthsLocalOnly, since if this is the only thing that's present we don't really
        // support the type at all: no type-specific methods will work with only this data.
        return $desc->hasExampleNumber()
            || static::descHasPossibleNumberData($desc)
            || $desc->hasNationalNumberPattern();
    }

    /**
     * Returns the types we have metadata for based on the PhoneMetadata object passed in.
     *
     * @param PhoneMetadata $metadata
     * @return array
     */
    private function getSupportedTypesForMetadata(PhoneMetadata $metadata)
    {
        $types = array();
        foreach (array_keys(PhoneNumberType::values()) as $type) {
            if ($type === PhoneNumberType::FIXED_LINE_OR_MOBILE || $type === PhoneNumberType::UNKNOWN) {
                // Never return FIXED_LINE_OR_MOBILE (it is a convenience type, and represents that a
                // particular number type can't be determined) or UNKNOWN (the non-type).
                continue;
            }

            if (self::descHasData($this->getNumberDescByType($metadata, $type))) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * Returns the types for a given region which the library has metadata for. Will not include
     * FIXED_LINE_OR_MOBILE (if the numbers in this region could be classified as FIXED_LINE_OR_MOBILE,
     * both FIXED_LINE and MOBILE would be present) and UNKNOWN.
     *
     * No types will be returned for invalid or unknown region codes.
     *
     * @param string $regionCode
     * @return array
     */
    public function getSupportedTypesForRegion($regionCode)
    {
        if (!$this->isValidRegionCode($regionCode)) {
            return array();
        }
        $metadata = $this->getMetadataForRegion($regionCode);
        return $this->getSupportedTypesForMetadata($metadata);
    }

    /**
     * Returns the types for a country-code belonging to a non-geographical entity which the library
     * has metadata for. Will not include FIXED_LINE_OR_MOBILE (if numbers for this non-geographical
     * entity could be classified as FIXED_LINE_OR_MOBILE, both FIXED_LINE and MOBILE would be
     * present) and UNKNOWN.
     *
     * @param int $countryCallingCode
     * @return array
     */
    public function getSupportedTypesForNonGeoEntity($countryCallingCode)
    {
        $metadata = $this->getMetadataForNonGeographicalRegion($countryCallingCode);
        if ($metadata === null) {
            return array();
        }

        return $this->getSupportedTypesForMetadata($metadata);
    }

    /**
     * Gets the length of the geographical area code from the {@code nationalNumber} field of the
     * PhoneNumber object passed in, so that clients could use it to split a national significant
     * number into geographical area code and subscriber number. It works in such a way that the
     * resultant subscriber number should be diallable, at least on some devices. An example of how
     * this could be used:
     *
     * <code>
     * $phoneUtil = PhoneNumberUtil::getInstance();
     * $number = $phoneUtil->parse("16502530000", "US");
     * $nationalSignificantNumber = $phoneUtil->getNationalSignificantNumber($number);
     *
     * $areaCodeLength = $phoneUtil->getLengthOfGeographicalAreaCode($number);
     * if ($areaCodeLength > 0)
     * {
     *     $areaCode = substr($nationalSignificantNumber, 0,$areaCodeLength);
     *     $subscriberNumber = substr($nationalSignificantNumber, $areaCodeLength);
     * } else {
     *     $areaCode = "";
     *     $subscriberNumber = $nationalSignificantNumber;
     * }
     * </code>
     *
     * N.B.: area code is a very ambiguous concept, so the I18N team generally recommends against
     * using it for most purposes, but recommends using the more general {@code nationalNumber}
     * instead. Read the following carefully before deciding to use this method:
     * <ul>
     *  <li> geographical area codes change over time, and this method honors those changes;
     *    therefore, it doesn't guarantee the stability of the result it produces.
     *  <li> subscriber numbers may not be diallable from all devices (notably mobile devices, which
     *    typically requires the full national_number to be dialled in most regions).
     *  <li> most non-geographical numbers have no area codes, including numbers from non-geographical
     *    entities
     *  <li> some geographical numbers have no area codes.
     * </ul>
     *
     * @param PhoneNumber $number PhoneNumber object for which clients want to know the length of the area code.
     * @return int the length of area code of the PhoneNumber object passed in.
     */
    public function getLengthOfGeographicalAreaCode(PhoneNumber $number)
    {
        $metadata = $this->getMetadataForRegion($this->getRegionCodeForNumber($number));
        if ($metadata === null) {
            return 0;
        }
        // If a country doesn't use a national prefix, and this number doesn't have an Italian leading
        // zero, we assume it is a closed dialling plan with no area codes.
        if (!$metadata->hasNationalPrefix() && !$number->isItalianLeadingZero()) {
            return 0;
        }

        $type = $this->getNumberType($number);
        $countryCallingCode = $number->getCountryCode();

        if ($type === PhoneNumberType::MOBILE
            // Note this is a rough heuristic; it doesn't cover Indonesia well, for example, where area
            // codes are present for some mobile phones but not for others. We have no better way of
            // representing this in the metadata at this point.
            && in_array($countryCallingCode, self::$GEO_MOBILE_COUNTRIES_WITHOUT_MOBILE_AREA_CODES)
        ) {
            return 0;
        }

        if (!$this->isNumberGeographical($type, $countryCallingCode)) {
            return 0;
        }

        return $this->getLengthOfNationalDestinationCode($number);
    }

    /**
     * Returns the metadata for the given region code or {@code null} if the region code is invalid
     * or unknown.
     *
     * @param string $regionCode
     * @return null|PhoneMetadata
     */
    public function getMetadataForRegion($regionCode)
    {
        if (!$this->isValidRegionCode($regionCode)) {
            return null;
        }

        return $this->metadataSource->getMetadataForRegion($regionCode);
    }

    /**
     * Helper function to check region code is not unknown or null.
     *
     * @param string $regionCode
     * @return bool
     */
    protected function isValidRegionCode($regionCode)
    {
        return $regionCode !== null && in_array($regionCode, $this->supportedRegions);
    }

    /**
     * Returns the region where a phone number is from. This could be used for geocoding at the region
     * level. Only guarantees correct results for valid, full numbers (not short-codes, or invalid
     * numbers).
     *
     * @param PhoneNumber $number the phone number whose origin we want to know
     * @return null|string  the region where the phone number is from, or null if no region matches this calling
     * code
     */
    public function getRegionCodeForNumber(PhoneNumber $number)
    {
        $countryCode = $number->getCountryCode();
        if (!isset($this->countryCallingCodeToRegionCodeMap[$countryCode])) {
            return null;
        }
        $regions = $this->countryCallingCodeToRegionCodeMap[$countryCode];
        if (count($regions) == 1) {
            return $regions[0];
        }

        return $this->getRegionCodeForNumberFromRegionList($number, $regions);
    }

    /**
     * Returns the region code for a number from the list of region codes passing in.
     *
     * @param PhoneNumber $number
     * @param array $regionCodes
     * @return null|string
     */
    protected function getRegionCodeForNumberFromRegionList(PhoneNumber $number, array $regionCodes)
    {
        $nationalNumber = $this->getNationalSignificantNumber($number);
        foreach ($regionCodes as $regionCode) {
            // If leadingDigits is present, use this. Otherwise, do full validation.
            // Metadata cannot be null because the region codes come from the country calling code map.
            $metadata = $this->getMetadataForRegion($regionCode);
            if ($metadata->hasLeadingDigits()) {
                $nbMatches = preg_match(
                    '/' . $metadata->getLeadingDigits() . '/',
                    $nationalNumber,
                    $matches,
                    PREG_OFFSET_CAPTURE
                );
                if ($nbMatches > 0 && $matches[0][1] === 0) {
                    return $regionCode;
                }
            } elseif ($this->getNumberTypeHelper($nationalNumber, $metadata) != PhoneNumberType::UNKNOWN) {
                return $regionCode;
            }
        }
        return null;
    }

    /**
     * Gets the national significant number of the a phone number. Note a national significant number
     * doesn't contain a national prefix or any formatting.
     *
     * @param PhoneNumber $number the phone number for which the national significant number is needed
     * @return string the national significant number of the PhoneNumber object passed in
     */
    public function getNationalSignificantNumber(PhoneNumber $number)
    {
        // If leading zero(s) have been set, we prefix this now. Note this is not a national prefix.
        $nationalNumber = '';
        if ($number->isItalianLeadingZero() && $number->getNumberOfLeadingZeros() > 0) {
            $zeros = str_repeat('0', $number->getNumberOfLeadingZeros());
            $nationalNumber .= $zeros;
        }
        $nationalNumber .= $number->getNationalNumber();
        return $nationalNumber;
    }

    /**
     * Returns the type of number passed in i.e Toll free, premium.
     *
     * @param string $nationalNumber
     * @param PhoneMetadata $metadata
     * @return int PhoneNumberType constant
     */
    protected function getNumberTypeHelper($nationalNumber, PhoneMetadata $metadata)
    {
        if (!$this->isNumberMatchingDesc($nationalNumber, $metadata->getGeneralDesc())) {
            return PhoneNumberType::UNKNOWN;
        }
        if ($this->isNumberMatchingDesc($nationalNumber, $metadata->getPremiumRate())) {
            return PhoneNumberType::PREMIUM_RATE;
        }
        if ($this->isNumberMatchingDesc($nationalNumber, $metadata->getTollFree())) {
            return PhoneNumberType::TOLL_FREE;
        }


        if ($this->isNumberMatchingDesc($nationalNumber, $metadata->getSharedCost())) {
            return PhoneNumberType::SHARED_COST;
        }
        if ($this->isNumberMatchingDesc($nationalNumber, $metadata->getVoip())) {
            return PhoneNumberType::VOIP;
        }
        if ($this->isNumberMatchingDesc($nationalNumber, $metadata->getPersonalNumber())) {
            return PhoneNumberType::PERSONAL_NUMBER;
        }
        if ($this->isNumberMatchingDesc($nationalNumber, $metadata->getPager())) {
            return PhoneNumberType::PAGER;
        }
        if ($this->isNumberMatchingDesc($nationalNumber, $metadata->getUan())) {
            return PhoneNumberType::UAN;
        }
        if ($this->isNumberMatchingDesc($nationalNumber, $metadata->getVoicemail())) {
            return PhoneNumberType::VOICEMAIL;
        }
        $isFixedLine = $this->isNumberMatchingDesc($nationalNumber, $metadata->getFixedLine());
        if ($isFixedLine) {
            if ($metadata->getSameMobileAndFixedLinePattern()) {
                return PhoneNumberType::FIXED_LINE_OR_MOBILE;
            }

            if ($this->isNumberMatchingDesc($nationalNumber, $metadata->getMobile())) {
                return PhoneNumberType::FIXED_LINE_OR_MOBILE;
            }
            return PhoneNumberType::FIXED_LINE;
        }
        // Otherwise, test to see if the number is mobile. Only do this if certain that the patterns for
        // mobile and fixed line aren't the same.
        if (!$metadata->getSameMobileAndFixedLinePattern() &&
            $this->isNumberMatchingDesc($nationalNumber, $metadata->getMobile())
        ) {
            return PhoneNumberType::MOBILE;
        }
        return PhoneNumberType::UNKNOWN;
    }

    /**
     * @param string $nationalNumber
     * @param PhoneNumberDesc $numberDesc
     * @return bool
     */
    public function isNumberMatchingDesc($nationalNumber, PhoneNumberDesc $numberDesc)
    {
        // Check if any possible number lengths are present; if so, we use them to avoid checking the
        // validation pattern if they don't match. If they are absent, this means they match the general
        // description, which we have already checked before checking a specific number type.
        $actualLength = mb_strlen($nationalNumber);
        $possibleLengths = $numberDesc->getPossibleLength();
        if (count($possibleLengths) > 0 && !in_array($actualLength, $possibleLengths)) {
            return false;
        }

        return $this->matcherAPI->matchNationalNumber($nationalNumber, $numberDesc, false);
    }

    /**
     * isNumberGeographical(PhoneNumber)
     *
     * Tests whether a phone number has a geographical association. It checks if the number is
     * associated with a certain region in the country to which it belongs. Note that this doesn't
     * verify if the number is actually in use.
     *
     * isNumberGeographical(PhoneNumberType, $countryCallingCode)
     *
     * Tests whether a phone number has a geographical association, as represented by its type and the
     * country it belongs to.
     *
     * This version exists since calculating the phone number type is expensive; if we have already
     * done this, we don't want to do it again.
     *
     * @param PhoneNumber|int $phoneNumberObjOrType A PhoneNumber object, or a PhoneNumberType integer
     * @param int|null $countryCallingCode Used when passing a PhoneNumberType
     * @return bool
     */
    public function isNumberGeographical($phoneNumberObjOrType, $countryCallingCode = null)
    {
        if ($phoneNumberObjOrType instanceof PhoneNumber) {
            return $this->isNumberGeographical($this->getNumberType($phoneNumberObjOrType), $phoneNumberObjOrType->getCountryCode());
        }

        return $phoneNumberObjOrType == PhoneNumberType::FIXED_LINE
        || $phoneNumberObjOrType == PhoneNumberType::FIXED_LINE_OR_MOBILE
        || (in_array($countryCallingCode, static::$GEO_MOBILE_COUNTRIES)
            && $phoneNumberObjOrType == PhoneNumberType::MOBILE);
    }

    /**
     * Gets the type of a valid phone number.
     *
     * @param PhoneNumber $number the number the phone number that we want to know the type
     * @return int PhoneNumberType the type of the phone number, or UNKNOWN if it is invalid
     */
    public function getNumberType(PhoneNumber $number)
    {
        $regionCode = $this->getRegionCodeForNumber($number);
        $metadata = $this->getMetadataForRegionOrCallingCode($number->getCountryCode(), $regionCode);
        if ($metadata === null) {
            return PhoneNumberType::UNKNOWN;
        }
        $nationalSignificantNumber = $this->getNationalSignificantNumber($number);
        return $this->getNumberTypeHelper($nationalSignificantNumber, $metadata);
    }

    /**
     * @param int $countryCallingCode
     * @param string $regionCode
     * @return null|PhoneMetadata
     */
    protected function getMetadataForRegionOrCallingCode($countryCallingCode, $regionCode)
    {
        return static::REGION_CODE_FOR_NON_GEO_ENTITY === $regionCode ?
            $this->getMetadataForNonGeographicalRegion($countryCallingCode) : $this->getMetadataForRegion($regionCode);
    }

    /**
     * @param int $countryCallingCode
     * @return null|PhoneMetadata
     */
    public function getMetadataForNonGeographicalRegion($countryCallingCode)
    {
        if (!isset($this->countryCallingCodeToRegionCodeMap[$countryCallingCode])) {
            return null;
        }
        return $this->metadataSource->getMetadataForNonGeographicalRegion($countryCallingCode);
    }

    /**
     * Gets the length of the national destination code (NDC) from the PhoneNumber object passed in,
     * so that clients could use it to split a national significant number into NDC and subscriber
     * number. The NDC of a phone number is normally the first group of digit(s) right after the
     * country calling code when the number is formatted in the international format, if there is a
     * subscriber number part that follows.
     *
     * follows.
     *
     * N.B.: similar to an area code, not all numbers have an NDC!
     *
     * An example of how this could be used:
     *
     * <code>
     * $phoneUtil = PhoneNumberUtil::getInstance();
     * $number = $phoneUtil->parse("18002530000", "US");
     * $nationalSignificantNumber = $phoneUtil->getNationalSignificantNumber($number);
     *
     * $nationalDestinationCodeLength = $phoneUtil->getLengthOfNationalDestinationCode($number);
     * if ($nationalDestinationCodeLength > 0) {
     *     $nationalDestinationCode = substr($nationalSignificantNumber, 0, $nationalDestinationCodeLength);
     *     $subscriberNumber = substr($nationalSignificantNumber, $nationalDestinationCodeLength);
     * } else {
     *     $nationalDestinationCode = "";
     *     $subscriberNumber = $nationalSignificantNumber;
     * }
     * </code>
     *
     * Refer to the unit tests to see the difference between this function and
     * {@link #getLengthOfGeographicalAreaCode}.
     *
     * @param PhoneNumber $number the PhoneNumber object for which clients want to know the length of the NDC.
     * @return int the length of NDC of the PhoneNumber object passed in, which could be zero
     */
    public function getLengthOfNationalDestinationCode(PhoneNumber $number)
    {
        if ($number->hasExtension()) {
            // We don't want to alter the proto given to us, but we don't want to include the extension
            // when we format it, so we copy it and clear the extension here.
            $copiedProto = new PhoneNumber();
            $copiedProto->mergeFrom($number);
            $copiedProto->clearExtension();
        } else {
            $copiedProto = clone $number;
        }

        $nationalSignificantNumber = $this->format($copiedProto, PhoneNumberFormat::INTERNATIONAL);

        $numberGroups = preg_split('/' . static::NON_DIGITS_PATTERN . '/', $nationalSignificantNumber);

        // The pattern will start with "+COUNTRY_CODE " so the first group will always be the empty
        // string (before the + symbol) and the second group will be the country calling code. The third
        // group will be area code if it is not the last group.
        if (count($numberGroups) <= 3) {
            return 0;
        }

        if ($this->getNumberType($number) == PhoneNumberType::MOBILE) {
            // For example Argentinian mobile numbers, when formatted in the international format, are in
            // the form of +54 9 NDC XXXX.... As a result, we take the length of the third group (NDC) and
            // add the length of the second group (which is the mobile token), which also forms part of
            // the national significant number. This assumes that the mobile token is always formatted
            // separately from the rest of the phone number.

            $mobileToken = static::getCountryMobileToken($number->getCountryCode());
            if ($mobileToken !== '') {
                return mb_strlen($numberGroups[2]) + mb_strlen($numberGroups[3]);
            }
        }
        return mb_strlen($numberGroups[2]);
    }

    /**
     * Formats a phone number in the specified format using default rules. Note that this does not
     * promise to produce a phone number that the user can dial from where they are - although we do
     * format in either 'national' or 'international' format depending on what the client asks for, we
     * do not currently support a more abbreviated format, such as for users in the same "area" who
     * could potentially dial the number without area code. Note that if the phone number has a
     * country calling code of 0 or an otherwise invalid country calling code, we cannot work out
     * which formatting rules to apply so we return the national significant number with no formatting
     * applied.
     *
     * @param PhoneNumber $number the phone number to be formatted
     * @param int $numberFormat the PhoneNumberFormat the phone number should be formatted into
     * @return string the formatted phone number
     */
    public function format(PhoneNumber $number, $numberFormat)
    {
        if ($number->getNationalNumber() == 0 && $number->hasRawInput()) {
            // Unparseable numbers that kept their raw input just use that.
            // This is the only case where a number can be formatted as E164 without a
            // leading '+' symbol (but the original number wasn't parseable anyway).
            // TODO: Consider removing the 'if' above so that unparseable
            // strings without raw input format to the empty string instead of "+00"
            $rawInput = $number->getRawInput();
            if (mb_strlen($rawInput) > 0) {
                return $rawInput;
            }
        }

        $formattedNumber = '';
        $countryCallingCode = $number->getCountryCode();
        $nationalSignificantNumber = $this->getNationalSignificantNumber($number);

        if ($numberFormat == PhoneNumberFormat::E164) {
            // Early exit for E164 case (even if the country calling code is invalid) since no formatting
            // of the national number needs to be applied. Extensions are not formatted.
            $formattedNumber .= $nationalSignificantNumber;
            $this->prefixNumberWithCountryCallingCode($countryCallingCode, PhoneNumberFormat::E164, $formattedNumber);
            return $formattedNumber;
        }

        if (!$this->hasValidCountryCallingCode($countryCallingCode)) {
            $formattedNumber .= $nationalSignificantNumber;
            return $formattedNumber;
        }

        // Note getRegionCodeForCountryCode() is used because formatting information for regions which
        // share a country calling code is contained by only one region for performance reasons. For
        // example, for NANPA regions it will be contained in the metadata for US.
        $regionCode = $this->getRegionCodeForCountryCode($countryCallingCode);
        // Metadata cannot be null because the country calling code is valid (which means that the
        // region code cannot be ZZ and must be one of our supported region codes).
        $metadata = $this->getMetadataForRegionOrCallingCode($countryCallingCode, $regionCode);
        $formattedNumber .= $this->formatNsn($nationalSignificantNumber, $metadata, $numberFormat);
        $this->maybeAppendFormattedExtension($number, $metadata, $numberFormat, $formattedNumber);
        $this->prefixNumberWithCountryCallingCode($countryCallingCode, $numberFormat, $formattedNumber);
        return $formattedNumber;
    }

    /**
     * A helper function that is used by format and formatByPattern.
     * @param int $countryCallingCode
     * @param int $numberFormat PhoneNumberFormat
     * @param string $formattedNumber
     */
    protected function prefixNumberWithCountryCallingCode($countryCallingCode, $numberFormat, &$formattedNumber)
    {
        switch ($numberFormat) {
            case PhoneNumberFormat::E164:
                $formattedNumber = static::PLUS_SIGN . $countryCallingCode . $formattedNumber;
                return;
            case PhoneNumberFormat::INTERNATIONAL:
                $formattedNumber = static::PLUS_SIGN . $countryCallingCode . ' ' . $formattedNumber;
                return;
            case PhoneNumberFormat::RFC3966:
                $formattedNumber = static::RFC3966_PREFIX . static::PLUS_SIGN . $countryCallingCode . '-' . $formattedNumber;
                return;
            case PhoneNumberFormat::NATIONAL:
            default:
                return;
        }
    }

    /**
     * Helper function to check the country calling code is valid.
     * @param int $countryCallingCode
     * @return bool
     */
    protected function hasValidCountryCallingCode($countryCallingCode)
    {
        return isset($this->countryCallingCodeToRegionCodeMap[$countryCallingCode]);
    }

    /**
     * Returns the region code that matches the specific country calling code. In the case of no
     * region code being found, ZZ will be returned. In the case of multiple regions, the one
     * designated in the metadata as the "main" region for this calling code will be returned. If the
     * countryCallingCode entered is valid but doesn't match a specific region (such as in the case of
     * non-geographical calling codes like 800) the value "001" will be returned (corresponding to
     * the value for World in the UN M.49 schema).
     *
     * @param int $countryCallingCode
     * @return string
     */
    public function getRegionCodeForCountryCode($countryCallingCode)
    {
        $regionCodes = isset($this->countryCallingCodeToRegionCodeMap[$countryCallingCode]) ? $this->countryCallingCodeToRegionCodeMap[$countryCallingCode] : null;
        return $regionCodes === null ? static::UNKNOWN_REGION : $regionCodes[0];
    }

    /**
     * Note in some regions, the national number can be written in two completely different ways
     * depending on whether it forms part of the NATIONAL format or INTERNATIONAL format. The
     * numberFormat parameter here is used to specify which format to use for those cases. If a
     * carrierCode is specified, this will be inserted into the formatted string to replace $CC.
     * @param string $number
     * @param PhoneMetadata $metadata
     * @param int $numberFormat PhoneNumberFormat
     * @param null|string $carrierCode
     * @return string
     */
    protected function formatNsn($number, PhoneMetadata $metadata, $numberFormat, $carrierCode = null)
    {
        $intlNumberFormats = $metadata->intlNumberFormats();
        // When the intlNumberFormats exists, we use that to format national number for the
        // INTERNATIONAL format instead of using the numberDesc.numberFormats.
        $availableFormats = (count($intlNumberFormats) == 0 || $numberFormat == PhoneNumberFormat::NATIONAL)
            ? $metadata->numberFormats()
            : $metadata->intlNumberFormats();
        $formattingPattern = $this->chooseFormattingPatternForNumber($availableFormats, $number);
        return ($formattingPattern === null)
            ? $number
            : $this->formatNsnUsingPattern($number, $formattingPattern, $numberFormat, $carrierCode);
    }

    /**
     * @param NumberFormat[] $availableFormats
     * @param string $nationalNumber
     * @return NumberFormat|null
     */
    public function chooseFormattingPatternForNumber(array $availableFormats, $nationalNumber)
    {
        foreach ($availableFormats as $numFormat) {
            $leadingDigitsPatternMatcher = null;
            $size = $numFormat->leadingDigitsPatternSize();
            // We always use the last leading_digits_pattern, as it is the most detailed.
            if ($size > 0) {
                $leadingDigitsPatternMatcher = new Matcher(
                    $numFormat->getLeadingDigitsPattern($size - 1),
                    $nationalNumber
                );
            }
            if ($size == 0 || $leadingDigitsPatternMatcher->lookingAt()) {
                $m = new Matcher($numFormat->getPattern(), $nationalNumber);
                if ($m->matches() > 0) {
                    return $numFormat;
                }
            }
        }
        return null;
    }

    /**
     * Note that carrierCode is optional - if null or an empty string, no carrier code replacement
     * will take place.
     * @param string $nationalNumber
     * @param NumberFormat $formattingPattern
     * @param int $numberFormat PhoneNumberFormat
     * @param null|string $carrierCode
     * @return string
     */
    public function formatNsnUsingPattern(
        $nationalNumber,
        NumberFormat $formattingPattern,
        $numberFormat,
        $carrierCode = null
    ) {
        $numberFormatRule = $formattingPattern->getFormat();
        $m = new Matcher($formattingPattern->getPattern(), $nationalNumber);
        if ($numberFormat === PhoneNumberFormat::NATIONAL &&
            $carrierCode !== null && mb_strlen($carrierCode) > 0 &&
            mb_strlen($formattingPattern->getDomesticCarrierCodeFormattingRule()) > 0
        ) {
            // Replace the $CC in the formatting rule with the desired carrier code.
            $carrierCodeFormattingRule = $formattingPattern->getDomesticCarrierCodeFormattingRule();
            $carrierCodeFormattingRule = str_replace(static::CC_STRING, $carrierCode, $carrierCodeFormattingRule);
            // Now replace the $FG in the formatting rule with the first group and the carrier code
            // combined in the appropriate way.
            $firstGroupMatcher = new Matcher(static::FIRST_GROUP_PATTERN, $numberFormatRule);
            $numberFormatRule = $firstGroupMatcher->replaceFirst($carrierCodeFormattingRule);
            $formattedNationalNumber = $m->replaceAll($numberFormatRule);
        } else {
            // Use the national prefix formatting rule instead.
            $nationalPrefixFormattingRule = $formattingPattern->getNationalPrefixFormattingRule();
            if ($numberFormat == PhoneNumberFormat::NATIONAL &&
                $nationalPrefixFormattingRule !== null &&
                mb_strlen($nationalPrefixFormattingRule) > 0
            ) {
                $firstGroupMatcher = new Matcher(static::FIRST_GROUP_PATTERN, $numberFormatRule);
                $formattedNationalNumber = $m->replaceAll(
                    $firstGroupMatcher->replaceFirst($nationalPrefixFormattingRule)
                );
            } else {
                $formattedNationalNumber = $m->replaceAll($numberFormatRule);
            }
        }
        if ($numberFormat == PhoneNumberFormat::RFC3966) {
            // Strip any leading punctuation.
            $matcher = new Matcher(static::$SEPARATOR_PATTERN, $formattedNationalNumber);
            if ($matcher->lookingAt()) {
                $formattedNationalNumber = $matcher->replaceFirst('');
            }
            // Replace the rest with a dash between each number group.
            $formattedNationalNumber = $matcher->reset($formattedNationalNumber)->replaceAll('-');
        }
        return $formattedNationalNumber;
    }

    /**
     * Appends the formatted extension of a phone number to formattedNumber, if the phone number had
     * an extension specified.
     *
     * @param PhoneNumber $number
     * @param PhoneMetadata|null $metadata
     * @param int $numberFormat PhoneNumberFormat
     * @param string $formattedNumber
     */
    protected function maybeAppendFormattedExtension(PhoneNumber $number, $metadata, $numberFormat, &$formattedNumber)
    {
        if ($number->hasExtension() && mb_strlen($number->getExtension()) > 0) {
            if ($numberFormat === PhoneNumberFormat::RFC3966) {
                $formattedNumber .= static::RFC3966_EXTN_PREFIX . $number->getExtension();
            } elseif (!empty($metadata) && $metadata->hasPreferredExtnPrefix()) {
                $formattedNumber .= $metadata->getPreferredExtnPrefix() . $number->getExtension();
            } else {
                $formattedNumber .= static::DEFAULT_EXTN_PREFIX . $number->getExtension();
            }
        }
    }

    /**
     * Returns the mobile token for the provided country calling code if it has one, otherwise
     * returns an empty string. A mobile token is a number inserted before the area code when dialing
     * a mobile number from that country from abroad.
     *
     * @param int $countryCallingCode the country calling code for which we want the mobile token
     * @return string the mobile token, as a string, for the given country calling code
     */
    public static function getCountryMobileToken($countryCallingCode)
    {
        if (count(static::$MOBILE_TOKEN_MAPPINGS) === 0) {
            static::initMobileTokenMappings();
        }

        if (array_key_exists($countryCallingCode, static::$MOBILE_TOKEN_MAPPINGS)) {
            return static::$MOBILE_TOKEN_MAPPINGS[$countryCallingCode];
        }
        return '';
    }

    /**
     * Checks if the number is a valid vanity (alpha) number such as 800 MICROSOFT. A valid vanity
     * number will start with at least 3 digits and will have three or more alpha characters. This
     * does not do region-specific checks - to work out if this number is actually valid for a region,
     * it should be parsed and methods such as {@link #isPossibleNumberWithReason} and
     * {@link #isValidNumber} should be used.
     *
     * @param string $number the number that needs to be checked
     * @return bool true if the number is a valid vanity number
     */
    public function isAlphaNumber($number)
    {
        if (!static::isViablePhoneNumber($number)) {
            // Number is too short, or doesn't match the basic phone number pattern.
            return false;
        }
        $this->maybeStripExtension($number);
        return (bool)preg_match('/' . static::VALID_ALPHA_PHONE_PATTERN . '/' . static::REGEX_FLAGS, $number);
    }

    /**
     * Checks to see if the string of characters could possibly be a phone number at all. At the
     * moment, checks to see that the string begins with at least 2 digits, ignoring any punctuation
     * commonly found in phone numbers.
     * This method does not require the number to be normalized in advance - but does assume that
     * leading non-number symbols have been removed, such as by the method extractPossibleNumber.
     *
     * @param string $number to be checked for viability as a phone number
     * @return boolean true if the number could be a phone number of some sort, otherwise false
     */
    public static function isViablePhoneNumber($number)
    {
        if (static::$VALID_PHONE_NUMBER_PATTERN === null) {
            static::initValidPhoneNumberPatterns();
        }

        if (mb_strlen($number) < static::MIN_LENGTH_FOR_NSN) {
            return false;
        }

        $validPhoneNumberPattern = static::getValidPhoneNumberPattern();

        $m = preg_match($validPhoneNumberPattern, $number);
        return $m > 0;
    }

    /**
     * We append optionally the extension pattern to the end here, as a valid phone number may
     * have an extension prefix appended, followed by 1 or more digits.
     * @return string
     */
    protected static function getValidPhoneNumberPattern()
    {
        return static::$VALID_PHONE_NUMBER_PATTERN;
    }

    /**
     * Strips any extension (as in, the part of the number dialled after the call is connected,
     * usually indicated with extn, ext, x or similar) from the end of the number, and returns it.
     *
     * @param string $number the non-normalized telephone number that we wish to strip the extension from
     * @return string the phone extension
     */
    protected function maybeStripExtension(&$number)
    {
        $matches = array();
        $find = preg_match(static::$EXTN_PATTERN, $number, $matches, PREG_OFFSET_CAPTURE);
        // If we find a potential extension, and the number preceding this is a viable number, we assume
        // it is an extension.
        if ($find > 0 && static::isViablePhoneNumber(substr($number, 0, $matches[0][1]))) {
            // The numbers are captured into groups in the regular expression.

            for ($i = 1, $length = count($matches); $i <= $length; $i++) {
                if ($matches[$i][0] != '') {
                    // We go through the capturing groups until we find one that captured some digits. If none
                    // did, then we will return the empty string.
                    $extension = $matches[$i][0];
                    $number = substr($number, 0, $matches[0][1]);
                    return $extension;
                }
            }
        }
        return '';
    }

    /**
     * Parses a string and returns it in proto buffer format. This method differs from {@link #parse}
     * in that it always populates the raw_input field of the protocol buffer with numberToParse as
     * well as the country_code_source field.
     *
     * @param string $numberToParse number that we are attempting to parse. This can contain formatting
     *                                  such as +, ( and -, as well as a phone number extension. It can also
     *                                  be provided in RFC3966 format.
     * @param string $defaultRegion region that we are expecting the number to be from. This is only used
     *                                  if the number being parsed is not written in international format.
     *                                  The country calling code for the number in this case would be stored
     *                                  as that of the default region supplied.
     * @param PhoneNumber $phoneNumber
     * @return PhoneNumber              a phone number proto buffer filled with the parsed number
     */
    public function parseAndKeepRawInput($numberToParse, $defaultRegion, PhoneNumber $phoneNumber = null)
    {
        if ($phoneNumber === null) {
            $phoneNumber = new PhoneNumber();
        }
        $this->parseHelper($numberToParse, $defaultRegion, true, true, $phoneNumber);
        return $phoneNumber;
    }

    /**
     * Returns an iterable over all PhoneNumberMatches in $text
     *
     * @param string $text
     * @param string $defaultRegion
     * @param AbstractLeniency $leniency Defaults to Leniency::VALID()
     * @param int $maxTries Defaults to PHP_INT_MAX
     * @return PhoneNumberMatcher
     */
    public function findNumbers($text, $defaultRegion, AbstractLeniency $leniency = null, $maxTries = PHP_INT_MAX)
    {
        if ($leniency === null) {
            $leniency = Leniency::VALID();
        }

        return new PhoneNumberMatcher($this, $text, $defaultRegion, $leniency, $maxTries);
    }

    /**
     * Gets an AsYouTypeFormatter for the specific region.
     *
     * @param string $regionCode The region where the phone number is being entered.
     * @return AsYouTypeFormatter
     */
    public function getAsYouTypeFormatter($regionCode)
    {
        return new AsYouTypeFormatter($regionCode);
    }

    /**
     * A helper function to set the values related to leading zeros in a PhoneNumber.
     * @param string $nationalNumber
     * @param PhoneNumber $phoneNumber
     */
    public static function setItalianLeadingZerosForPhoneNumber($nationalNumber, PhoneNumber $phoneNumber)
    {
        if (strlen($nationalNumber) > 1 && substr($nationalNumber, 0, 1) == '0') {
            $phoneNumber->setItalianLeadingZero(true);
            $numberOfLeadingZeros = 1;
            // Note that if the national number is all "0"s, the last "0" is not counted as a leading
            // zero.
            while ($numberOfLeadingZeros < (strlen($nationalNumber) - 1) &&
                substr($nationalNumber, $numberOfLeadingZeros, 1) == '0') {
                $numberOfLeadingZeros++;
            }

            if ($numberOfLeadingZeros != 1) {
                $phoneNumber->setNumberOfLeadingZeros($numberOfLeadingZeros);
            }
        }
    }

    /**
     * Parses a string and fills up the phoneNumber. This method is the same as the public
     * parse() method, with the exception that it allows the default region to be null, for use by
     * isNumberMatch(). checkRegion should be set to false if it is permitted for the default region
     * to be null or unknown ("ZZ").
     * @param string $numberToParse
     * @param string $defaultRegion
     * @param bool $keepRawInput
     * @param bool $checkRegion
     * @param PhoneNumber $phoneNumber
     * @throws NumberParseException
     */
    protected function parseHelper($numberToParse, $defaultRegion, $keepRawInput, $checkRegion, PhoneNumber $phoneNumber)
    {
        if ($numberToParse === null) {
            throw new NumberParseException(NumberParseException::NOT_A_NUMBER, 'The phone number supplied was null.');
        }

        $numberToParse = trim($numberToParse);

        if (mb_strlen($numberToParse) > static::MAX_INPUT_STRING_LENGTH) {
            throw new NumberParseException(
                NumberParseException::TOO_LONG,
                'The string supplied was too long to parse.'
            );
        }

        $nationalNumber = '';
        $this->buildNationalNumberForParsing($numberToParse, $nationalNumber);

        if (!static::isViablePhoneNumber($nationalNumber)) {
            throw new NumberParseException(
                NumberParseException::NOT_A_NUMBER,
                'The string supplied did not seem to be a phone number.'
            );
        }

        // Check the region supplied is valid, or that the extracted number starts with some sort of +
        // sign so the number's region can be determined.
        if ($checkRegion && !$this->checkRegionForParsing($nationalNumber, $defaultRegion)) {
            throw new NumberParseException(
                NumberParseException::INVALID_COUNTRY_CODE,
                'Missing or invalid default region.'
            );
        }

        if ($keepRawInput) {
            $phoneNumber->setRawInput($numberToParse);
        }
        // Attempt to parse extension first, since it doesn't require region-specific data and we want
        // to have the non-normalised number here.
        $extension = $this->maybeStripExtension($nationalNumber);
        if (mb_strlen($extension) > 0) {
            $phoneNumber->setExtension($extension);
        }

        $regionMetadata = $this->getMetadataForRegion($defaultRegion);
        // Check to see if the number is given in international format so we know whether this number is
        // from the default region or not.
        $normalizedNationalNumber = '';
        try {
            // TODO: This method should really just take in the string buffer that has already
            // been created, and just remove the prefix, rather than taking in a string and then
            // outputting a string buffer.
            $countryCode = $this->maybeExtractCountryCode(
                $nationalNumber,
                $regionMetadata,
                $normalizedNationalNumber,
                $keepRawInput,
                $phoneNumber
            );
        } catch (NumberParseException $e) {
            $matcher = new Matcher(static::$PLUS_CHARS_PATTERN, $nationalNumber);
            if ($e->getErrorType() == NumberParseException::INVALID_COUNTRY_CODE && $matcher->lookingAt()) {
                // Strip the plus-char, and try again.
                $countryCode = $this->maybeExtractCountryCode(
                    substr($nationalNumber, $matcher->end()),
                    $regionMetadata,
                    $normalizedNationalNumber,
                    $keepRawInput,
                    $phoneNumber
                );
                if ($countryCode == 0) {
                    throw new NumberParseException(
                        NumberParseException::INVALID_COUNTRY_CODE,
                        'Could not interpret numbers after plus-sign.'
                    );
                }
            } else {
                throw new NumberParseException($e->getErrorType(), $e->getMessage(), $e);
            }
        }
        if ($countryCode !== 0) {
            $phoneNumberRegion = $this->getRegionCodeForCountryCode($countryCode);
            if ($phoneNumberRegion != $defaultRegion) {
                // Metadata cannot be null because the country calling code is valid.
                $regionMetadata = $this->getMetadataForRegionOrCallingCode($countryCode, $phoneNumberRegion);
            }
        } else {
            // If no extracted country calling code, use the region supplied instead. The national number
            // is just the normalized version of the number we were given to parse.

            $normalizedNationalNumber .= static::normalize($nationalNumber);
            if ($defaultRegion !== null) {
                $countryCode = $regionMetadata->getCountryCode();
                $phoneNumber->setCountryCode($countryCode);
            } elseif ($keepRawInput) {
                $phoneNumber->clearCountryCodeSource();
            }
        }
        if (mb_strlen($normalizedNationalNumber) < static::MIN_LENGTH_FOR_NSN) {
            throw new NumberParseException(
                NumberParseException::TOO_SHORT_NSN,
                'The string supplied is too short to be a phone number.'
            );
        }
        if ($regionMetadata !== null) {
            $carrierCode = '';
            $potentialNationalNumber = $normalizedNationalNumber;
            $this->maybeStripNationalPrefixAndCarrierCode($potentialNationalNumber, $regionMetadata, $carrierCode);
            // We require that the NSN remaining after stripping the national prefix and carrier code be
            // long enough to be a possible length for the region. Otherwise, we don't do the stripping,
            // since the original number could be a valid short number.
            $validationResult = $this->testNumberLength($potentialNationalNumber, $regionMetadata);
            if ($validationResult !== ValidationResult::TOO_SHORT
                && $validationResult !== ValidationResult::IS_POSSIBLE_LOCAL_ONLY
                && $validationResult !== ValidationResult::INVALID_LENGTH) {
                $normalizedNationalNumber = $potentialNationalNumber;
                if ($keepRawInput && mb_strlen($carrierCode) > 0) {
                    $phoneNumber->setPreferredDomesticCarrierCode($carrierCode);
                }
            }
        }
        $lengthOfNationalNumber = mb_strlen($normalizedNationalNumber);
        if ($lengthOfNationalNumber < static::MIN_LENGTH_FOR_NSN) {
            throw new NumberParseException(
                NumberParseException::TOO_SHORT_NSN,
                'The string supplied is too short to be a phone number.'
            );
        }
        if ($lengthOfNationalNumber > static::MAX_LENGTH_FOR_NSN) {
            throw new NumberParseException(
                NumberParseException::TOO_LONG,
                'The string supplied is too long to be a phone number.'
            );
        }
        static::setItalianLeadingZerosForPhoneNumber($normalizedNationalNumber, $phoneNumber);

        /*
         * We have to store the National Number as a string instead of a "long" as Google do
         *
         * Since PHP doesn't always support 64 bit INTs, this was a float, but that had issues
         * with long numbers.
         *
         * We have to remove the leading zeroes ourself though
         */
        if ((int)$normalizedNationalNumber == 0) {
            $normalizedNationalNumber = '0';
        } else {
            $normalizedNationalNumber = ltrim($normalizedNationalNumber, '0');
        }

        $phoneNumber->setNationalNumber($normalizedNationalNumber);
    }

    /**
     * Returns a new phone number containing only the fields needed to uniquely identify a phone
     * number, rather than any fields that capture the context in which  the phone number was created.
     * These fields correspond to those set in parse() rather than parseAndKeepRawInput()
     *
     * @param PhoneNumber $phoneNumberIn
     * @return PhoneNumber
     */
    protected static function copyCoreFieldsOnly(PhoneNumber $phoneNumberIn)
    {
        $phoneNumber = new PhoneNumber();
        $phoneNumber->setCountryCode($phoneNumberIn->getCountryCode());
        $phoneNumber->setNationalNumber($phoneNumberIn->getNationalNumber());
        if (mb_strlen($phoneNumberIn->getExtension()) > 0) {
            $phoneNumber->setExtension($phoneNumberIn->getExtension());
        }
        if ($phoneNumberIn->isItalianLeadingZero()) {
            $phoneNumber->setItalianLeadingZero(true);
            // This field is only relevant if there are leading zeros at all.
            $phoneNumber->setNumberOfLeadingZeros($phoneNumberIn->getNumberOfLeadingZeros());
        }
        return $phoneNumber;
    }

    /**
     * Converts numberToParse to a form that we can parse and write it to nationalNumber if it is
     * written in RFC3966; otherwise extract a possible number out of it and write to nationalNumber.
     * @param string $numberToParse
     * @param string $nationalNumber
     */
    protected function buildNationalNumberForParsing($numberToParse, &$nationalNumber)
    {
        $indexOfPhoneContext = strpos($numberToParse, static::RFC3966_PHONE_CONTEXT);
        if ($indexOfPhoneContext !== false) {
            $phoneContextStart = $indexOfPhoneContext + mb_strlen(static::RFC3966_PHONE_CONTEXT);
            // If the phone context contains a phone number prefix, we need to capture it, whereas domains
            // will be ignored.
            if ($phoneContextStart < (strlen($numberToParse) - 1)
                && substr($numberToParse, $phoneContextStart, 1) == static::PLUS_SIGN) {
                // Additional parameters might follow the phone context. If so, we will remove them here
                // because the parameters after phone context are not important for parsing the
                // phone number.
                $phoneContextEnd = strpos($numberToParse, ';', $phoneContextStart);
                if ($phoneContextEnd > 0) {
                    $nationalNumber .= substr($numberToParse, $phoneContextStart, $phoneContextEnd - $phoneContextStart);
                } else {
                    $nationalNumber .= substr($numberToParse, $phoneContextStart);
                }
            }

            // Now append everything between the "tel:" prefix and the phone-context. This should include
            // the national number, an optional extension or isdn-subaddress component. Note we also
            // handle the case when "tel:" is missing, as we have seen in some of the phone number inputs.
            // In that case, we append everything from the beginning.

            $indexOfRfc3966Prefix = strpos($numberToParse, static::RFC3966_PREFIX);
            $indexOfNationalNumber = ($indexOfRfc3966Prefix !== false) ? $indexOfRfc3966Prefix + strlen(static::RFC3966_PREFIX) : 0;
            $nationalNumber .= substr(
                $numberToParse,
                $indexOfNationalNumber,
                $indexOfPhoneContext - $indexOfNationalNumber
            );
        } else {
            // Extract a possible number from the string passed in (this strips leading characters that
            // could not be the start of a phone number.)
            $nationalNumber .= static::extractPossibleNumber($numberToParse);
        }

        // Delete the isdn-subaddress and everything after it if it is present. Note extension won't
        // appear at the same time with isdn-subaddress according to paragraph 5.3 of the RFC3966 spec,
        $indexOfIsdn = strpos($nationalNumber, static::RFC3966_ISDN_SUBADDRESS);
        if ($indexOfIsdn > 0) {
            $nationalNumber = substr($nationalNumber, 0, $indexOfIsdn);
        }
        // If both phone context and isdn-subaddress are absent but other parameters are present, the
        // parameters are left in nationalNumber. This is because we are concerned about deleting
        // content from a potential number string when there is no strong evidence that the number is
        // actually written in RFC3966.
    }

    /**
     * Attempts to extract a possible number from the string passed in. This currently strips all
     * leading characters that cannot be used to start a phone number. Characters that can be used to
     * start a phone number are defined in the VALID_START_CHAR_PATTERN. If none of these characters
     * are found in the number passed in, an empty string is returned. This function also attempts to
     * strip off any alternative extensions or endings if two or more are present, such as in the case
     * of: (530) 583-6985 x302/x2303. The second extension here makes this actually two phone numbers,
     * (530) 583-6985 x302 and (530) 583-6985 x2303. We remove the second extension so that the first
     * number is parsed correctly.
     *
     * @param int $number the string that might contain a phone number
     * @return string the number, stripped of any non-phone-number prefix (such as "Tel:") or an empty
     *                string if no character used to start phone numbers (such as + or any digit) is
     *                found in the number
     */
    public static function extractPossibleNumber($number)
    {
        if (static::$VALID_START_CHAR_PATTERN === null) {
            static::initValidStartCharPattern();
        }

        $matches = array();
        $match = preg_match('/' . static::$VALID_START_CHAR_PATTERN . '/ui', $number, $matches, PREG_OFFSET_CAPTURE);
        if ($match > 0) {
            $number = substr($number, $matches[0][1]);
            // Remove trailing non-alpha non-numerical characters.
            $trailingCharsMatcher = new Matcher(static::$UNWANTED_END_CHAR_PATTERN, $number);
            if ($trailingCharsMatcher->find() && $trailingCharsMatcher->start() > 0) {
                $number = substr($number, 0, $trailingCharsMatcher->start());
            }

            // Check for extra numbers at the end.
            $match = preg_match('%' . static::$SECOND_NUMBER_START_PATTERN . '%', $number, $matches, PREG_OFFSET_CAPTURE);
            if ($match > 0) {
                $number = substr($number, 0, $matches[0][1]);
            }

            return $number;
        }

        return '';
    }

    /**
     * Checks to see that the region code used is valid, or if it is not valid, that the number to
     * parse starts with a + symbol so that we can attempt to infer the region from the number.
     * Returns false if it cannot use the region provided and the region cannot be inferred.
     * @param string $numberToParse
     * @param string $defaultRegion
     * @return bool
     */
    protected function checkRegionForParsing($numberToParse, $defaultRegion)
    {
        if (!$this->isValidRegionCode($defaultRegion)) {
            // If the number is null or empty, we can't infer the region.
            $plusCharsPatternMatcher = new Matcher(static::$PLUS_CHARS_PATTERN, $numberToParse);
            if ($numberToParse === null || mb_strlen($numberToParse) == 0 || !$plusCharsPatternMatcher->lookingAt()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Tries to extract a country calling code from a number. This method will return zero if no
     * country calling code is considered to be present. Country calling codes are extracted in the
     * following ways:
     * <ul>
     *  <li> by stripping the international dialing prefix of the region the person is dialing from,
     *       if this is present in the number, and looking at the next digits
     *  <li> by stripping the '+' sign if present and then looking at the next digits
     *  <li> by comparing the start of the number and the country calling code of the default region.
     *       If the number is not considered possible for the numbering plan of the default region
     *       initially, but starts with the country calling code of this region, validation will be
     *       reattempted after stripping this country calling code. If this number is considered a
     *       possible number, then the first digits will be considered the country calling code and
     *       removed as such.
     * </ul>
     * It will throw a NumberParseException if the number starts with a '+' but the country calling
     * code supplied after this does not match that of any known region.
     *
     * @param string $number non-normalized telephone number that we wish to extract a country calling
     *     code from - may begin with '+'
     * @param PhoneMetadata $defaultRegionMetadata metadata about the region this number may be from
     * @param string $nationalNumber a string buffer to store the national significant number in, in the case
     *     that a country calling code was extracted. The number is appended to any existing contents.
     *     If no country calling code was extracted, this will be left unchanged.
     * @param bool $keepRawInput true if the country_code_source and preferred_carrier_code fields of
     *     phoneNumber should be populated.
     * @param PhoneNumber $phoneNumber the PhoneNumber object where the country_code and country_code_source need
     *     to be populated. Note the country_code is always populated, whereas country_code_source is
     *     only populated when keepCountryCodeSource is true.
     * @return int the country calling code extracted or 0 if none could be extracted
     * @throws NumberParseException
     */
    public function maybeExtractCountryCode(
        $number,
        PhoneMetadata $defaultRegionMetadata = null,
        &$nationalNumber,
        $keepRawInput,
        PhoneNumber $phoneNumber
    ) {
        if (mb_strlen($number) == 0) {
            return 0;
        }
        $fullNumber = $number;
        // Set the default prefix to be something that will never match.
        $possibleCountryIddPrefix = 'NonMatch';
        if ($defaultRegionMetadata !== null) {
            $possibleCountryIddPrefix = $defaultRegionMetadata->getInternationalPrefix();
        }
        $countryCodeSource = $this->maybeStripInternationalPrefixAndNormalize($fullNumber, $possibleCountryIddPrefix);

        if ($keepRawInput) {
            $phoneNumber->setCountryCodeSource($countryCodeSource);
        }
        if ($countryCodeSource != CountryCodeSource::FROM_DEFAULT_COUNTRY) {
            if (mb_strlen($fullNumber) <= static::MIN_LENGTH_FOR_NSN) {
                throw new NumberParseException(
                    NumberParseException::TOO_SHORT_AFTER_IDD,
                    'Phone number had an IDD, but after this was not long enough to be a viable phone number.'
                );
            }
            $potentialCountryCode = $this->extractCountryCode($fullNumber, $nationalNumber);

            if ($potentialCountryCode != 0) {
                $phoneNumber->setCountryCode($potentialCountryCode);
                return $potentialCountryCode;
            }

            // If this fails, they must be using a strange country calling code that we don't recognize,
            // or that doesn't exist.
            throw new NumberParseException(
                NumberParseException::INVALID_COUNTRY_CODE,
                'Country calling code supplied was not recognised.'
            );
        }

        if ($defaultRegionMetadata !== null) {
            // Check to see if the number starts with the country calling code for the default region. If
            // so, we remove the country calling code, and do some checks on the validity of the number
            // before and after.
            $defaultCountryCode = $defaultRegionMetadata->getCountryCode();
            $defaultCountryCodeString = (string)$defaultCountryCode;
            $normalizedNumber = $fullNumber;
            if (strpos($normalizedNumber, $defaultCountryCodeString) === 0) {
                $potentialNationalNumber = substr($normalizedNumber, mb_strlen($defaultCountryCodeString));
                $generalDesc = $defaultRegionMetadata->getGeneralDesc();
                // Don't need the carrier code.
                $carriercode = null;
                $this->maybeStripNationalPrefixAndCarrierCode(
                    $potentialNationalNumber,
                    $defaultRegionMetadata,
                    $carriercode
                );
                // If the number was not valid before but is valid now, or if it was too long before, we
                // consider the number with the country calling code stripped to be a better result and
                // keep that instead.
                if ((!$this->matcherAPI->matchNationalNumber($fullNumber, $generalDesc, false)
                        && $this->matcherAPI->matchNationalNumber($potentialNationalNumber, $generalDesc, false))
                    || $this->testNumberLength($fullNumber, $defaultRegionMetadata) === ValidationResult::TOO_LONG
                ) {
                    $nationalNumber .= $potentialNationalNumber;
                    if ($keepRawInput) {
                        $phoneNumber->setCountryCodeSource(CountryCodeSource::FROM_NUMBER_WITHOUT_PLUS_SIGN);
                    }
                    $phoneNumber->setCountryCode($defaultCountryCode);
                    return $defaultCountryCode;
                }
            }
        }
        // No country calling code present.
        $phoneNumber->setCountryCode(0);
        return 0;
    }

    /**
     * Strips any international prefix (such as +, 00, 011) present in the number provided, normalizes
     * the resulting number, and indicates if an international prefix was present.
     *
     * @param string $number the non-normalized telephone number that we wish to strip any international
     *     dialing prefix from.
     * @param string $possibleIddPrefix string the international direct dialing prefix from the region we
     *     think this number may be dialed in
     * @return int the corresponding CountryCodeSource if an international dialing prefix could be
     *     removed from the number, otherwise CountryCodeSource.FROM_DEFAULT_COUNTRY if the number did
     *     not seem to be in international format.
     */
    public function maybeStripInternationalPrefixAndNormalize(&$number, $possibleIddPrefix)
    {
        if (mb_strlen($number) == 0) {
            return CountryCodeSource::FROM_DEFAULT_COUNTRY;
        }
        $matches = array();
        // Check to see if the number begins with one or more plus signs.
        $match = preg_match('/^' . static::$PLUS_CHARS_PATTERN . '/' . static::REGEX_FLAGS, $number, $matches, PREG_OFFSET_CAPTURE);
        if ($match > 0) {
            $number = mb_substr($number, $matches[0][1] + mb_strlen($matches[0][0]));
            // Can now normalize the rest of the number since we've consumed the "+" sign at the start.
            $number = static::normalize($number);
            return CountryCodeSource::FROM_NUMBER_WITH_PLUS_SIGN;
        }
        // Attempt to parse the first digits as an international prefix.
        $iddPattern = $possibleIddPrefix;
        $number = static::normalize($number);
        return $this->parsePrefixAsIdd($iddPattern, $number)
            ? CountryCodeSource::FROM_NUMBER_WITH_IDD
            : CountryCodeSource::FROM_DEFAULT_COUNTRY;
    }

    /**
     * Normalizes a string of characters representing a phone number. This performs
     * the following conversions:
     *   Punctuation is stripped.
     *   For ALPHA/VANITY numbers:
     *   Letters are converted to their numeric representation on a telephone
     *       keypad. The keypad used here is the one defined in ITU Recommendation
     *       E.161. This is only done if there are 3 or more letters in the number,
     *       to lessen the risk that such letters are typos.
     *   For other numbers:
     *    - Wide-ascii digits are converted to normal ASCII (European) digits.
     *    - Arabic-Indic numerals are converted to European numerals.
     *    - Spurious alpha characters are stripped.
     *
     * @param string $number a string of characters representing a phone number.
     * @return string the normalized string version of the phone number.
     */
    public static function normalize(&$number)
    {
        if (static::$ALPHA_PHONE_MAPPINGS === null) {
            static::initAlphaPhoneMappings();
        }

        $m = new Matcher(static::VALID_ALPHA_PHONE_PATTERN, $number);
        if ($m->matches()) {
            return static::normalizeHelper($number, static::$ALPHA_PHONE_MAPPINGS, true);
        }

        return static::normalizeDigitsOnly($number);
    }

    /**
     * Normalizes a string of characters representing a phone number. This converts wide-ascii and
     * arabic-indic numerals to European numerals, and strips punctuation and alpha characters.
     *
     * @param $number string  a string of characters representing a phone number
     * @return string the normalized string version of the phone number
     */
    public static function normalizeDigitsOnly($number)
    {
        return static::normalizeDigits($number, false /* strip non-digits */);
    }

    /**
     * @param string $number
     * @param bool $keepNonDigits
     * @return string
     */
    public static function normalizeDigits($number, $keepNonDigits)
    {
        $normalizedDigits = '';
        $numberAsArray = preg_split('/(?<!^)(?!$)/u', $number);
        foreach ($numberAsArray as $character) {
            // Check if we are in the unicode number range
            if (array_key_exists($character, static::$numericCharacters)) {
                $normalizedDigits .= static::$numericCharacters[$character];
            } elseif (is_numeric($character)) {
                $normalizedDigits .= $character;
            } elseif ($keepNonDigits) {
                $normalizedDigits .= $character;
            }
        }
        return $normalizedDigits;
    }

    /**
     * Strips the IDD from the start of the number if present. Helper function used by
     * maybeStripInternationalPrefixAndNormalize.
     * @param string $iddPattern
     * @param string $number
     * @return bool
     */
    protected function parsePrefixAsIdd($iddPattern, &$number)
    {
        $m = new Matcher($iddPattern, $number);
        if ($m->lookingAt()) {
            $matchEnd = $m->end();
            // Only strip this if the first digit after the match is not a 0, since country calling codes
            // cannot begin with 0.
            $digitMatcher = new Matcher(static::$CAPTURING_DIGIT_PATTERN, substr($number, $matchEnd));
            if ($digitMatcher->find()) {
                $normalizedGroup = static::normalizeDigitsOnly($digitMatcher->group(1));
                if ($normalizedGroup == '0') {
                    return false;
                }
            }
            $number = substr($number, $matchEnd);
            return true;
        }
        return false;
    }

    /**
     * Extracts country calling code from fullNumber, returns it and places the remaining number in  nationalNumber.
     * It assumes that the leading plus sign or IDD has already been removed.
     * Returns 0 if fullNumber doesn't start with a valid country calling code, and leaves nationalNumber unmodified.
     * @param string $fullNumber
     * @param string $nationalNumber
     * @return int
     * @internal
     */
    public function extractCountryCode($fullNumber, &$nationalNumber)
    {
        if ((mb_strlen($fullNumber) == 0) || ($fullNumber[0] == '0')) {
            // Country codes do not begin with a '0'.
            return 0;
        }
        $numberLength = mb_strlen($fullNumber);
        for ($i = 1; $i <= static::MAX_LENGTH_COUNTRY_CODE && $i <= $numberLength; $i++) {
            $potentialCountryCode = (int)substr($fullNumber, 0, $i);
            if (isset($this->countryCallingCodeToRegionCodeMap[$potentialCountryCode])) {
                $nationalNumber .= substr($fullNumber, $i);
                return $potentialCountryCode;
            }
        }
        return 0;
    }

    /**
     * Strips any national prefix (such as 0, 1) present in the number provided.
     *
     * @param string $number the normalized telephone number that we wish to strip any national
     *     dialing prefix from
     * @param PhoneMetadata $metadata the metadata for the region that we think this number is from
     * @param string $carrierCode a place to insert the carrier code if one is extracted
     * @return bool true if a national prefix or carrier code (or both) could be extracted.
     */
    public function maybeStripNationalPrefixAndCarrierCode(&$number, PhoneMetadata $metadata, &$carrierCode)
    {
        $numberLength = mb_strlen($number);
        $possibleNationalPrefix = $metadata->getNationalPrefixForParsing();
        if ($numberLength == 0 || $possibleNationalPrefix === null || mb_strlen($possibleNationalPrefix) == 0) {
            // Early return for numbers of zero length.
            return false;
        }

        // Attempt to parse the first digits as a national prefix.
        $prefixMatcher = new Matcher($possibleNationalPrefix, $number);
        if ($prefixMatcher->lookingAt()) {
            $generalDesc = $metadata->getGeneralDesc();
            // Check if the original number is viable.
            $isViableOriginalNumber = $this->matcherAPI->matchNationalNumber($number, $generalDesc, false);
            // $prefixMatcher->group($numOfGroups) === null implies nothing was captured by the capturing
            // groups in $possibleNationalPrefix; therefore, no transformation is necessary, and we just
            // remove the national prefix
            $numOfGroups = $prefixMatcher->groupCount();
            $transformRule = $metadata->getNationalPrefixTransformRule();
            if ($transformRule === null
                || mb_strlen($transformRule) == 0
                || $prefixMatcher->group($numOfGroups - 1) === null
            ) {
                // If the original number was viable, and the resultant number is not, we return.
                if ($isViableOriginalNumber &&
                    !$this->matcherAPI->matchNationalNumber(
                        substr($number, $prefixMatcher->end()),
                        $generalDesc,
                        false
                    )) {
                    return false;
                }
                if ($carrierCode !== null && $numOfGroups > 0 && $prefixMatcher->group($numOfGroups) !== null) {
                    $carrierCode .= $prefixMatcher->group(1);
                }

                $number = substr($number, $prefixMatcher->end());
                return true;
            }

            // Check that the resultant number is still viable. If not, return. Check this by copying
            // the string and making the transformation on the copy first.
            $transformedNumber = $number;
            $transformedNumber = substr_replace(
                $transformedNumber,
                $prefixMatcher->replaceFirst($transformRule),
                0,
                $numberLength
            );
            if ($isViableOriginalNumber
                && !$this->matcherAPI->matchNationalNumber($transformedNumber, $generalDesc, false)) {
                return false;
            }
            if ($carrierCode !== null && $numOfGroups > 1) {
                $carrierCode .= $prefixMatcher->group(1);
            }
            $number = substr_replace($number, $transformedNumber, 0, mb_strlen($number));
            return true;
        }
        return false;
    }

    /**
     * Convenience wrapper around isPossibleNumberForTypeWithReason. Instead of returning the reason
     * for failure, this method returns true if the number is either a possible fully-qualified
     * number (containing the area code and country code), or if the number could be a possible local
     * number (with a country code, but missing an area code). Local numbers are considered possible
     * if they could be possibly dialled in this format: if the area code is needed for a call to
     * connect, the number is not considered possible without it.
     *
     * @param PhoneNumber $number The number that needs to be checked
     * @param int $type PhoneNumberType The type we are interested in
     * @return bool true if the number is possible for this particular type
     */
    public function isPossibleNumberForType(PhoneNumber $number, $type)
    {
        $result = $this->isPossibleNumberForTypeWithReason($number, $type);
        return $result === ValidationResult::IS_POSSIBLE
            || $result === ValidationResult::IS_POSSIBLE_LOCAL_ONLY;
    }

    /**
     * Helper method to check a number against possible lengths for this number type, and determine
     * whether it matches, or is too short or too long.
     *
     * @param string $number
     * @param PhoneMetadata $metadata
     * @param int $type PhoneNumberType
     * @return int ValidationResult
     */
    protected function testNumberLength($number, PhoneMetadata $metadata, $type = PhoneNumberType::UNKNOWN)
    {
        $descForType = $this->getNumberDescByType($metadata, $type);
        // There should always be "possibleLengths" set for every element. This is declared in the XML
        // schema which is verified by PhoneNumberMetadataSchemaTest.
        // For size efficiency, where a sub-description (e.g. fixed-line) has the same possibleLengths
        // as the parent, this is missing, so we fall back to the general desc (where no numbers of the
        // type exist at all, there is one possible length (-1) which is guaranteed not to match the
        // length of any real phone number).
        $possibleLengths = (count($descForType->getPossibleLength()) === 0)
            ? $metadata->getGeneralDesc()->getPossibleLength() : $descForType->getPossibleLength();

        $localLengths = $descForType->getPossibleLengthLocalOnly();

        if ($type === PhoneNumberType::FIXED_LINE_OR_MOBILE) {
            if (!static::descHasPossibleNumberData($this->getNumberDescByType($metadata, PhoneNumberType::FIXED_LINE))) {
                // The rate case has been encountered where no fixedLine data is available (true for some
                // non-geographical entities), so we just check mobile.
                return $this->testNumberLength($number, $metadata, PhoneNumberType::MOBILE);
            }

            $mobileDesc = $this->getNumberDescByType($metadata, PhoneNumberType::MOBILE);
            if (static::descHasPossibleNumberData($mobileDesc)) {
                // Note that when adding the possible lengths from mobile, we have to again check they
                // aren't empty since if they are this indicates they are the same as the general desc and
                // should be obtained from there.
                $possibleLengths = array_merge(
                    $possibleLengths,
                    (count($mobileDesc->getPossibleLength()) === 0)
                        ? $metadata->getGeneralDesc()->getPossibleLength() : $mobileDesc->getPossibleLength()
                );

                // The current list is sorted; we need to merge in the new list and re-sort (duplicates
                // are okay). Sorting isn't so expensive because the lists are very small.
                sort($possibleLengths);

                if (count($localLengths) === 0) {
                    $localLengths = $mobileDesc->getPossibleLengthLocalOnly();
                } else {
                    $localLengths = array_merge($localLengths, $mobileDesc->getPossibleLengthLocalOnly());
                    sort($localLengths);
                }
            }
        }


        // If the type is not supported at all (indicated by the possible lengths containing -1 at this
        // point) we return invalid length.

        if ($possibleLengths[0] === -1) {
            return ValidationResult::INVALID_LENGTH;
        }

        $actualLength = mb_strlen($number);

        // This is safe because there is never an overlap between the possible lengths and the local-only
        // lengths; this is checked at build time.

        if (in_array($actualLength, $localLengths)) {
            return ValidationResult::IS_POSSIBLE_LOCAL_ONLY;
        }

        $minimumLength = reset($possibleLengths);
        if ($minimumLength == $actualLength) {
            return ValidationResult::IS_POSSIBLE;
        }

        if ($minimumLength > $actualLength) {
            return ValidationResult::TOO_SHORT;
        } elseif (isset($possibleLengths[count($possibleLengths) - 1]) && $possibleLengths[count($possibleLengths) - 1] < $actualLength) {
            return ValidationResult::TOO_LONG;
        }

        // We skip the first element; we've already checked it.
        array_shift($possibleLengths);
        return in_array($actualLength, $possibleLengths) ? ValidationResult::IS_POSSIBLE : ValidationResult::INVALID_LENGTH;
    }

    /**
     * Returns a list with the region codes that match the specific country calling code. For
     * non-geographical country calling codes, the region code 001 is returned. Also, in the case
     * of no region code being found, an empty list is returned.
     * @param int $countryCallingCode
     * @return array
     */
    public function getRegionCodesForCountryCode($countryCallingCode)
    {
        $regionCodes = isset($this->countryCallingCodeToRegionCodeMap[$countryCallingCode]) ? $this->countryCallingCodeToRegionCodeMap[$countryCallingCode] : null;
        return $regionCodes === null ? array() : $regionCodes;
    }

    /**
     * Returns the country calling code for a specific region. For example, this would be 1 for the
     * United States, and 64 for New Zealand. Assumes the region is already valid.
     *
     * @param string $regionCode the region that we want to get the country calling code for
     * @return int the country calling code for the region denoted by regionCode
     */
    public function getCountryCodeForRegion($regionCode)
    {
        if (!$this->isValidRegionCode($regionCode)) {
            return 0;
        }
        return $this->getCountryCodeForValidRegion($regionCode);
    }

    /**
     * Returns the country calling code for a specific region. For example, this would be 1 for the
     * United States, and 64 for New Zealand. Assumes the region is already valid.
     *
     * @param string $regionCode the region that we want to get the country calling code for
     * @return int the country calling code for the region denoted by regionCode
     * @throws \InvalidArgumentException if the region is invalid
     */
    protected function getCountryCodeForValidRegion($regionCode)
    {
        $metadata = $this->getMetadataForRegion($regionCode);
        if ($metadata === null) {
            throw new \InvalidArgumentException('Invalid region code: ' . $regionCode);
        }
        return $metadata->getCountryCode();
    }

    /**
     * Returns a number formatted in such a way that it can be dialed from a mobile phone in a
     * specific region. If the number cannot be reached from the region (e.g. some countries block
     * toll-free numbers from being called outside of the country), the method returns an empty
     * string.
     *
     * @param PhoneNumber $number the phone number to be formatted
     * @param string $regionCallingFrom the region where the call is being placed
     * @param boolean $withFormatting whether the number should be returned with formatting symbols, such as
     *     spaces and dashes.
     * @return string the formatted phone number
     */
    public function formatNumberForMobileDialing(PhoneNumber $number, $regionCallingFrom, $withFormatting)
    {
        $countryCallingCode = $number->getCountryCode();
        if (!$this->hasValidCountryCallingCode($countryCallingCode)) {
            return $number->hasRawInput() ? $number->getRawInput() : '';
        }

        $formattedNumber = '';
        // Clear the extension, as that part cannot normally be dialed together with the main number.
        $numberNoExt = new PhoneNumber();
        $numberNoExt->mergeFrom($number)->clearExtension();
        $regionCode = $this->getRegionCodeForCountryCode($countryCallingCode);
        $numberType = $this->getNumberType($numberNoExt);
        $isValidNumber = ($numberType !== PhoneNumberType::UNKNOWN);
        if ($regionCallingFrom == $regionCode) {
            $isFixedLineOrMobile = ($numberType == PhoneNumberType::FIXED_LINE) || ($numberType == PhoneNumberType::MOBILE) || ($numberType == PhoneNumberType::FIXED_LINE_OR_MOBILE);
            // Carrier codes may be needed in some countries. We handle this here.
            if ($regionCode == 'CO' && $numberType == PhoneNumberType::FIXED_LINE) {
                $formattedNumber = $this->formatNationalNumberWithCarrierCode(
                    $numberNoExt,
                    static::COLOMBIA_MOBILE_TO_FIXED_LINE_PREFIX
                );
            } elseif ($regionCode == 'BR' && $isFixedLineOrMobile) {
                // Historically, we set this to an empty string when parsing with raw input if none was
                // found in the input string. However, this doesn't result in a number we can dial. For this
                // reason, we treat the empty string the same as if it isn't set at all.
                $formattedNumber = mb_strlen($numberNoExt->getPreferredDomesticCarrierCode()) > 0
                    ? $this->formatNationalNumberWithPreferredCarrierCode($numberNoExt, '')
                    // Brazilian fixed line and mobile numbers need to be dialed with a carrier code when
                    // called within Brazil. Without that, most of the carriers won't connect the call.
                    // Because of that, we return an empty string here.
                    : '';
            } elseif ($countryCallingCode === static::NANPA_COUNTRY_CODE) {
                // For NANPA countries, we output international format for numbers that can be dialed
                // internationally, since that always works, except for numbers which might potentially be
                // short numbers, which are always dialled in national format.
                $regionMetadata = $this->getMetadataForRegion($regionCallingFrom);
                if ($this->canBeInternationallyDialled($numberNoExt)
                    && $this->testNumberLength($this->getNationalSignificantNumber($numberNoExt), $regionMetadata)
                    !== ValidationResult::TOO_SHORT
                ) {
                    $formattedNumber = $this->format($numberNoExt, PhoneNumberFormat::INTERNATIONAL);
                } else {
                    $formattedNumber = $this->format($numberNoExt, PhoneNumberFormat::NATIONAL);
                }
            } elseif ((
                $regionCode == static::REGION_CODE_FOR_NON_GEO_ENTITY ||
                    // MX fixed line and mobile numbers should always be formatted in international format,
                    // even when dialed within MX. For national format to work, a carrier code needs to be
                    // used, and the correct carrier code depends on if the caller and callee are from the
                    // same local area. It is trickier to get that to work correctly than using
                    // international format, which is tested to work fine on all carriers.
                    // CL fixed line numbers need the national prefix when dialing in the national format,
                    // but don't have it when used for display. The reverse is true for mobile numbers.
                    // As a result, we output them in the international format to make it work.
                    (
                        ($regionCode === 'MX' || $regionCode === 'CL' || $regionCode === 'UZ')
                        && $isFixedLineOrMobile
                    )
            ) && $this->canBeInternationallyDialled($numberNoExt)
            ) {
                $formattedNumber = $this->format($numberNoExt, PhoneNumberFormat::INTERNATIONAL);
            } else {
                $formattedNumber = $this->format($numberNoExt, PhoneNumberFormat::NATIONAL);
            }
        } elseif ($isValidNumber && $this->canBeInternationallyDialled($numberNoExt)) {
            // We assume that short numbers are not diallable from outside their region, so if a number
            // is not a valid regular length phone number, we treat it as if it cannot be internationally
            // dialled.
            return $withFormatting ?
                $this->format($numberNoExt, PhoneNumberFormat::INTERNATIONAL) :
                $this->format($numberNoExt, PhoneNumberFormat::E164);
        }
        return $withFormatting ? $formattedNumber : static::normalizeDiallableCharsOnly($formattedNumber);
    }

    /**
     * Formats a phone number in national format for dialing using the carrier as specified in the
     * {@code carrierCode}. The {@code carrierCode} will always be used regardless of whether the
     * phone number already has a preferred domestic carrier code stored. If {@code carrierCode}
     * contains an empty string, returns the number in national format without any carrier code.
     *
     * @param PhoneNumber $number the phone number to be formatted
     * @param string $carrierCode the carrier selection code to be used
     * @return string the formatted phone number in national format for dialing using the carrier as
     * specified in the {@code carrierCode}
     */
    public function formatNationalNumberWithCarrierCode(PhoneNumber $number, $carrierCode)
    {
        $countryCallingCode = $number->getCountryCode();
        $nationalSignificantNumber = $this->getNationalSignificantNumber($number);
        if (!$this->hasValidCountryCallingCode($countryCallingCode)) {
            return $nationalSignificantNumber;
        }

        // Note getRegionCodeForCountryCode() is used because formatting information for regions which
        // share a country calling code is contained by only one region for performance reasons. For
        // example, for NANPA regions it will be contained in the metadata for US.
        $regionCode = $this->getRegionCodeForCountryCode($countryCallingCode);
        // Metadata cannot be null because the country calling code is valid.
        $metadata = $this->getMetadataForRegionOrCallingCode($countryCallingCode, $regionCode);

        $formattedNumber = $this->formatNsn(
            $nationalSignificantNumber,
            $metadata,
            PhoneNumberFormat::NATIONAL,
            $carrierCode
        );
        $this->maybeAppendFormattedExtension($number, $metadata, PhoneNumberFormat::NATIONAL, $formattedNumber);
        $this->prefixNumberWithCountryCallingCode(
            $countryCallingCode,
            PhoneNumberFormat::NATIONAL,
            $formattedNumber
        );
        return $formattedNumber;
    }

    /**
     * Formats a phone number in national format for dialing using the carrier as specified in the
     * preferredDomesticCarrierCode field of the PhoneNumber object passed in. If that is missing,
     * use the {@code fallbackCarrierCode} passed in instead. If there is no
     * {@code preferredDomesticCarrierCode}, and the {@code fallbackCarrierCode} contains an empty
     * string, return the number in national format without any carrier code.
     *
     * <p>Use {@link #formatNationalNumberWithCarrierCode} instead if the carrier code passed in
     * should take precedence over the number's {@code preferredDomesticCarrierCode} when formatting.
     *
     * @param PhoneNumber $number the phone number to be formatted
     * @param string $fallbackCarrierCode the carrier selection code to be used, if none is found in the
     *     phone number itself
     * @return string the formatted phone number in national format for dialing using the number's
     *     {@code preferredDomesticCarrierCode}, or the {@code fallbackCarrierCode} passed in if
     *     none is found
     */
    public function formatNationalNumberWithPreferredCarrierCode(PhoneNumber $number, $fallbackCarrierCode)
    {
        return $this->formatNationalNumberWithCarrierCode(
            $number,
            // Historically, we set this to an empty string when parsing with raw input if none was
            // found in the input string. However, this doesn't result in a number we can dial. For this
            // reason, we treat the empty string the same as if it isn't set at all.
            mb_strlen($number->getPreferredDomesticCarrierCode()) > 0
                ? $number->getPreferredDomesticCarrierCode()
                : $fallbackCarrierCode
        );
    }

    /**
     * Returns true if the number can be dialled from outside the region, or unknown. If the number
     * can only be dialled from within the region, returns false. Does not check the number is a valid
     * number. Note that, at the moment, this method does not handle short numbers (which are
     * currently all presumed to not be diallable from outside their country).
     *
     * @param PhoneNumber $number the phone-number for which we want to know whether it is diallable from outside the region
     * @return bool
     */
    public function canBeInternationallyDialled(PhoneNumber $number)
    {
        $metadata = $this->getMetadataForRegion($this->getRegionCodeForNumber($number));
        if ($metadata === null) {
            // Note numbers belonging to non-geographical entities (e.g. +800 numbers) are always
            // internationally diallable, and will be caught here.
            return true;
        }
        $nationalSignificantNumber = $this->getNationalSignificantNumber($number);
        return !$this->isNumberMatchingDesc($nationalSignificantNumber, $metadata->getNoInternationalDialling());
    }

    /**
     * Normalizes a string of characters representing a phone number. This strips all characters which
     * are not diallable on a mobile phone keypad (including all non-ASCII digits).
     *
     * @param string $number a string of characters representing a phone number
     * @return string the normalized string version of the phone number
     */
    public static function normalizeDiallableCharsOnly($number)
    {
        if (count(static::$DIALLABLE_CHAR_MAPPINGS) === 0) {
            static::initDiallableCharMappings();
        }

        return static::normalizeHelper($number, static::$DIALLABLE_CHAR_MAPPINGS, true /* remove non matches */);
    }

    /**
     * Formats a phone number for out-of-country dialing purposes.
     *
     * Note that in this version, if the number was entered originally using alpha characters and
     * this version of the number is stored in raw_input, this representation of the number will be
     * used rather than the digit representation. Grouping information, as specified by characters
     * such as "-" and " ", will be retained.
     *
     * <p><b>Caveats:</b></p>
     * <ul>
     *  <li> This will not produce good results if the country calling code is both present in the raw
     *       input _and_ is the start of the national number. This is not a problem in the regions
     *       which typically use alpha numbers.
     *  <li> This will also not produce good results if the raw input has any grouping information
     *       within the first three digits of the national number, and if the function needs to strip
     *       preceding digits/words in the raw input before these digits. Normally people group the
     *       first three digits together so this is not a huge problem - and will be fixed if it
     *       proves to be so.
     * </ul>
     *
     * @param PhoneNumber $number the phone number that needs to be formatted
     * @param String $regionCallingFrom the region where the call is being placed
     * @return String the formatted phone number
     */
    public function formatOutOfCountryKeepingAlphaChars(PhoneNumber $number, $regionCallingFrom)
    {
        $rawInput = $number->getRawInput();
        // If there is no raw input, then we can't keep alpha characters because there aren't any.
        // In this case, we return formatOutOfCountryCallingNumber.
        if (mb_strlen($rawInput) == 0) {
            return $this->formatOutOfCountryCallingNumber($number, $regionCallingFrom);
        }
        $countryCode = $number->getCountryCode();
        if (!$this->hasValidCountryCallingCode($countryCode)) {
            return $rawInput;
        }
        // Strip any prefix such as country calling code, IDD, that was present. We do this by comparing
        // the number in raw_input with the parsed number.
        // To do this, first we normalize punctuation. We retain number grouping symbols such as " "
        // only.
        $rawInput = self::normalizeHelper($rawInput, static::$ALL_PLUS_NUMBER_GROUPING_SYMBOLS, true);
        // Now we trim everything before the first three digits in the parsed number. We choose three
        // because all valid alpha numbers have 3 digits at the start - if it does not, then we don't
        // trim anything at all. Similarly, if the national number was less than three digits, we don't
        // trim anything at all.
        $nationalNumber = $this->getNationalSignificantNumber($number);
        if (mb_strlen($nationalNumber) > 3) {
            $firstNationalNumberDigit = strpos($rawInput, substr($nationalNumber, 0, 3));
            if ($firstNationalNumberDigit !== false) {
                $rawInput = substr($rawInput, $firstNationalNumberDigit);
            }
        }
        $metadataForRegionCallingFrom = $this->getMetadataForRegion($regionCallingFrom);
        if ($countryCode == static::NANPA_COUNTRY_CODE) {
            if ($this->isNANPACountry($regionCallingFrom)) {
                return $countryCode . ' ' . $rawInput;
            }
        } elseif ($metadataForRegionCallingFrom !== null &&
            $countryCode == $this->getCountryCodeForValidRegion($regionCallingFrom)
        ) {
            $formattingPattern =
                $this->chooseFormattingPatternForNumber(
                    $metadataForRegionCallingFrom->numberFormats(),
                    $nationalNumber
                );
            if ($formattingPattern === null) {
                // If no pattern above is matched, we format the original input.
                return $rawInput;
            }
            $newFormat = new NumberFormat();
            $newFormat->mergeFrom($formattingPattern);
            // The first group is the first group of digits that the user wrote together.
            $newFormat->setPattern("(\\d+)(.*)");
            // Here we just concatenate them back together after the national prefix has been fixed.
            $newFormat->setFormat('$1$2');
            // Now we format using this pattern instead of the default pattern, but with the national
            // prefix prefixed if necessary.
            // This will not work in the cases where the pattern (and not the leading digits) decide
            // whether a national prefix needs to be used, since we have overridden the pattern to match
            // anything, but that is not the case in the metadata to date.
            return $this->formatNsnUsingPattern($rawInput, $newFormat, PhoneNumberFormat::NATIONAL);
        }
        $internationalPrefixForFormatting = '';
        // If an unsupported region-calling-from is entered, or a country with multiple international
        // prefixes, the international format of the number is returned, unless there is a preferred
        // international prefix.
        if ($metadataForRegionCallingFrom !== null) {
            $internationalPrefix = $metadataForRegionCallingFrom->getInternationalPrefix();
            $uniqueInternationalPrefixMatcher = new Matcher(static::SINGLE_INTERNATIONAL_PREFIX, $internationalPrefix);
            $internationalPrefixForFormatting =
                $uniqueInternationalPrefixMatcher->matches()
                    ? $internationalPrefix
                    : $metadataForRegionCallingFrom->getPreferredInternationalPrefix();
        }
        $formattedNumber = $rawInput;
        $regionCode = $this->getRegionCodeForCountryCode($countryCode);
        // Metadata cannot be null because the country calling code is valid.
        $metadataForRegion = $this->getMetadataForRegionOrCallingCode($countryCode, $regionCode);
        $this->maybeAppendFormattedExtension(
            $number,
            $metadataForRegion,
            PhoneNumberFormat::INTERNATIONAL,
            $formattedNumber
        );
        if (mb_strlen($internationalPrefixForFormatting) > 0) {
            $formattedNumber = $internationalPrefixForFormatting . ' ' . $countryCode . ' ' . $formattedNumber;
        } else {
            // Invalid region entered as country-calling-from (so no metadata was found for it) or the
            // region chosen has multiple international dialling prefixes.
            $this->prefixNumberWithCountryCallingCode(
                $countryCode,
                PhoneNumberFormat::INTERNATIONAL,
                $formattedNumber
            );
        }
        return $formattedNumber;
    }

    /**
     * Formats a phone number for out-of-country dialing purposes. If no regionCallingFrom is
     * supplied, we format the number in its INTERNATIONAL format. If the country calling code is the
     * same as that of the region where the number is from, then NATIONAL formatting will be applied.
     *
     * <p>If the number itself has a country calling code of zero or an otherwise invalid country
     * calling code, then we return the number with no formatting applied.
     *
     * <p>Note this function takes care of the case for calling inside of NANPA and between Russia and
     * Kazakhstan (who share the same country calling code). In those cases, no international prefix
     * is used. For regions which have multiple international prefixes, the number in its
     * INTERNATIONAL format will be returned instead.
     *
     * @param PhoneNumber $number the phone number to be formatted
     * @param string $regionCallingFrom the region where the call is being placed
     * @return string  the formatted phone number
     */
    public function formatOutOfCountryCallingNumber(PhoneNumber $number, $regionCallingFrom)
    {
        if (!$this->isValidRegionCode($regionCallingFrom)) {
            return $this->format($number, PhoneNumberFormat::INTERNATIONAL);
        }
        $countryCallingCode = $number->getCountryCode();
        $nationalSignificantNumber = $this->getNationalSignificantNumber($number);
        if (!$this->hasValidCountryCallingCode($countryCallingCode)) {
            return $nationalSignificantNumber;
        }
        if ($countryCallingCode == static::NANPA_COUNTRY_CODE) {
            if ($this->isNANPACountry($regionCallingFrom)) {
                // For NANPA regions, return the national format for these regions but prefix it with the
                // country calling code.
                return $countryCallingCode . ' ' . $this->format($number, PhoneNumberFormat::NATIONAL);
            }
        } elseif ($countryCallingCode == $this->getCountryCodeForValidRegion($regionCallingFrom)) {
            // If regions share a country calling code, the country calling code need not be dialled.
            // This also applies when dialling within a region, so this if clause covers both these cases.
            // Technically this is the case for dialling from La Reunion to other overseas departments of
            // France (French Guiana, Martinique, Guadeloupe), but not vice versa - so we don't cover this
            // edge case for now and for those cases return the version including country calling code.
            // Details here: http://www.petitfute.com/voyage/225-info-pratiques-reunion
            return $this->format($number, PhoneNumberFormat::NATIONAL);
        }
        // Metadata cannot be null because we checked 'isValidRegionCode()' above.
        /** @var PhoneMetadata $metadataForRegionCallingFrom */
        $metadataForRegionCallingFrom = $this->getMetadataForRegion($regionCallingFrom);

        $internationalPrefix = $metadataForRegionCallingFrom->getInternationalPrefix();

        // In general, if there is a preferred international prefix, use that. Otherwise, for regions
        // that have multiple international prefixes, the international format of the number is
        // returned since we would not know which one to use.
        $internationalPrefixForFormatting = '';
        if ($metadataForRegionCallingFrom->hasPreferredInternationalPrefix()) {
            $internationalPrefixForFormatting = $metadataForRegionCallingFrom->getPreferredInternationalPrefix();
        } else {
            $uniqueInternationalPrefixMatcher = new Matcher(static::SINGLE_INTERNATIONAL_PREFIX, $internationalPrefix);

            if ($uniqueInternationalPrefixMatcher->matches()) {
                $internationalPrefixForFormatting = $internationalPrefix;
            }
        }

        $regionCode = $this->getRegionCodeForCountryCode($countryCallingCode);
        // Metadata cannot be null because the country calling code is valid.
        /** @var PhoneMetadata $metadataForRegion */
        $metadataForRegion = $this->getMetadataForRegionOrCallingCode($countryCallingCode, $regionCode);
        $formattedNationalNumber = $this->formatNsn(
            $nationalSignificantNumber,
            $metadataForRegion,
            PhoneNumberFormat::INTERNATIONAL
        );
        $formattedNumber = $formattedNationalNumber;
        $this->maybeAppendFormattedExtension(
            $number,
            $metadataForRegion,
            PhoneNumberFormat::INTERNATIONAL,
            $formattedNumber
        );
        if (mb_strlen($internationalPrefixForFormatting) > 0) {
            $formattedNumber = $internationalPrefixForFormatting . ' ' . $countryCallingCode . ' ' . $formattedNumber;
        } else {
            $this->prefixNumberWithCountryCallingCode(
                $countryCallingCode,
                PhoneNumberFormat::INTERNATIONAL,
                $formattedNumber
            );
        }
        return $formattedNumber;
    }

    /**
     * Checks if this is a region under the North American Numbering Plan Administration (NANPA).
     * @param string $regionCode
     * @return boolean true if regionCode is one of the regions under NANPA
     */
    public function isNANPACountry($regionCode)
    {
        return in_array($regionCode, $this->nanpaRegions);
    }

    /**
     * Formats a phone number using the original phone number format that the number is parsed from.
     * The original format is embedded in the country_code_source field of the PhoneNumber object
     * passed in. If such information is missing, the number will be formatted into the NATIONAL
     * format by default. When we don't have a formatting pattern for the number, the method returns
     * the raw input when it is available.
     *
     * Note this method guarantees no digit will be inserted, removed or modified as a result of
     * formatting.
     *
     * @param PhoneNumber $number the phone number that needs to be formatted in its original number format
     * @param string $regionCallingFrom the region whose IDD needs to be prefixed if the original number
     *     has one
     * @return string the formatted phone number in its original number format
     */
    public function formatInOriginalFormat(PhoneNumber $number, $regionCallingFrom)
    {
        if ($number->hasRawInput() && !$this->hasFormattingPatternForNumber($number)) {
            // We check if we have the formatting pattern because without that, we might format the number
            // as a group without national prefix.
            return $number->getRawInput();
        }
        if (!$number->hasCountryCodeSource()) {
            return $this->format($number, PhoneNumberFormat::NATIONAL);
        }
        switch ($number->getCountryCodeSource()) {
            case CountryCodeSource::FROM_NUMBER_WITH_PLUS_SIGN:
                $formattedNumber = $this->format($number, PhoneNumberFormat::INTERNATIONAL);
                break;
            case CountryCodeSource::FROM_NUMBER_WITH_IDD:
                $formattedNumber = $this->formatOutOfCountryCallingNumber($number, $regionCallingFrom);
                break;
            case CountryCodeSource::FROM_NUMBER_WITHOUT_PLUS_SIGN:
                $formattedNumber = substr($this->format($number, PhoneNumberFormat::INTERNATIONAL), 1);
                break;
            case CountryCodeSource::FROM_DEFAULT_COUNTRY:
                // Fall-through to default case.
            default:

                $regionCode = $this->getRegionCodeForCountryCode($number->getCountryCode());
                // We strip non-digits from the NDD here, and from the raw input later, so that we can
                // compare them easily.
                $nationalPrefix = $this->getNddPrefixForRegion($regionCode, true /* strip non-digits */);
                $nationalFormat = $this->format($number, PhoneNumberFormat::NATIONAL);
                if ($nationalPrefix === null || mb_strlen($nationalPrefix) == 0) {
                    // If the region doesn't have a national prefix at all, we can safely return the national
                    // format without worrying about a national prefix being added.
                    $formattedNumber = $nationalFormat;
                    break;
                }
                // Otherwise, we check if the original number was entered with a national prefix.
                if ($this->rawInputContainsNationalPrefix(
                    $number->getRawInput(),
                    $nationalPrefix,
                    $regionCode
                )
                ) {
                    // If so, we can safely return the national format.
                    $formattedNumber = $nationalFormat;
                    break;
                }
                // Metadata cannot be null here because getNddPrefixForRegion() (above) returns null if
                // there is no metadata for the region.
                $metadata = $this->getMetadataForRegion($regionCode);
                $nationalNumber = $this->getNationalSignificantNumber($number);
                $formatRule = $this->chooseFormattingPatternForNumber($metadata->numberFormats(), $nationalNumber);
                // The format rule could still be null here if the national number was 0 and there was no
                // raw input (this should not be possible for numbers generated by the phonenumber library
                // as they would also not have a country calling code and we would have exited earlier).
                if ($formatRule === null) {
                    $formattedNumber = $nationalFormat;
                    break;
                }
                // When the format we apply to this number doesn't contain national prefix, we can just
                // return the national format.
                // TODO: Refactor the code below with the code in isNationalPrefixPresentIfRequired.
                $candidateNationalPrefixRule = $formatRule->getNationalPrefixFormattingRule();
                // We assume that the first-group symbol will never be _before_ the national prefix.
                $indexOfFirstGroup = strpos($candidateNationalPrefixRule, '$1');
                if ($indexOfFirstGroup <= 0) {
                    $formattedNumber = $nationalFormat;
                    break;
                }
                $candidateNationalPrefixRule = substr($candidateNationalPrefixRule, 0, $indexOfFirstGroup);
                $candidateNationalPrefixRule = static::normalizeDigitsOnly($candidateNationalPrefixRule);
                if (mb_strlen($candidateNationalPrefixRule) == 0) {
                    // National prefix not used when formatting this number.
                    $formattedNumber = $nationalFormat;
                    break;
                }
                // Otherwise, we need to remove the national prefix from our output.
                $numFormatCopy = new NumberFormat();
                $numFormatCopy->mergeFrom($formatRule);
                $numFormatCopy->clearNationalPrefixFormattingRule();
                $numberFormats = array();
                $numberFormats[] = $numFormatCopy;
                $formattedNumber = $this->formatByPattern($number, PhoneNumberFormat::NATIONAL, $numberFormats);
                break;
        }
        $rawInput = $number->getRawInput();
        // If no digit is inserted/removed/modified as a result of our formatting, we return the
        // formatted phone number; otherwise we return the raw input the user entered.
        if ($formattedNumber !== null && mb_strlen($rawInput) > 0) {
            $normalizedFormattedNumber = static::normalizeDiallableCharsOnly($formattedNumber);
            $normalizedRawInput = static::normalizeDiallableCharsOnly($rawInput);
            if ($normalizedFormattedNumber != $normalizedRawInput) {
                $formattedNumber = $rawInput;
            }
        }
        return $formattedNumber;
    }

    /**
     * @param PhoneNumber $number
     * @return bool
     */
    protected function hasFormattingPatternForNumber(PhoneNumber $number)
    {
        $countryCallingCode = $number->getCountryCode();
        $phoneNumberRegion = $this->getRegionCodeForCountryCode($countryCallingCode);
        $metadata = $this->getMetadataForRegionOrCallingCode($countryCallingCode, $phoneNumberRegion);
        if ($metadata === null) {
            return false;
        }
        $nationalNumber = $this->getNationalSignificantNumber($number);
        $formatRule = $this->chooseFormattingPatternForNumber($metadata->numberFormats(), $nationalNumber);
        return $formatRule !== null;
    }

    /**
     * Returns the national dialling prefix for a specific region. For example, this would be 1 for
     * the United States, and 0 for New Zealand. Set stripNonDigits to true to strip symbols like "~"
     * (which indicates a wait for a dialling tone) from the prefix returned. If no national prefix is
     * present, we return null.
     *
     * <p>Warning: Do not use this method for do-your-own formatting - for some regions, the
     * national dialling prefix is used only for certain types of numbers. Use the library's
     * formatting functions to prefix the national prefix when required.
     *
     * @param string $regionCode the region that we want to get the dialling prefix for
     * @param boolean $stripNonDigits true to strip non-digits from the national dialling prefix
     * @return string the dialling prefix for the region denoted by regionCode
     */
    public function getNddPrefixForRegion($regionCode, $stripNonDigits)
    {
        $metadata = $this->getMetadataForRegion($regionCode);
        if ($metadata === null) {
            return null;
        }
        $nationalPrefix = $metadata->getNationalPrefix();
        // If no national prefix was found, we return null.
        if (mb_strlen($nationalPrefix) == 0) {
            return null;
        }
        if ($stripNonDigits) {
            // Note: if any other non-numeric symbols are ever used in national prefixes, these would have
            // to be removed here as well.
            $nationalPrefix = str_replace('~', '', $nationalPrefix);
        }
        return $nationalPrefix;
    }

    /**
     * Check if rawInput, which is assumed to be in the national format, has a national prefix. The
     * national prefix is assumed to be in digits-only form.
     * @param string $rawInput
     * @param string $nationalPrefix
     * @param string $regionCode
     * @return bool
     */
    protected function rawInputContainsNationalPrefix($rawInput, $nationalPrefix, $regionCode)
    {
        $normalizedNationalNumber = static::normalizeDigitsOnly($rawInput);
        if (strpos($normalizedNationalNumber, $nationalPrefix) === 0) {
            try {
                // Some Japanese numbers (e.g. 00777123) might be mistaken to contain the national prefix
                // when written without it (e.g. 0777123) if we just do prefix matching. To tackle that, we
                // check the validity of the number if the assumed national prefix is removed (777123 won't
                // be valid in Japan).
                return $this->isValidNumber(
                    $this->parse(substr($normalizedNationalNumber, mb_strlen($nationalPrefix)), $regionCode)
                );
            } catch (NumberParseException $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Tests whether a phone number matches a valid pattern. Note this doesn't verify the number
     * is actually in use, which is impossible to tell by just looking at a number itself. It only
     * verifies whether the parsed, canonicalised number is valid: not whether a particular series of
     * digits entered by the user is diallable from the region provided when parsing. For example, the
     * number +41 (0) 78 927 2696 can be parsed into a number with country code "41" and national
     * significant number "789272696". This is valid, while the original string is not diallable.
     *
     * @param PhoneNumber $number the phone number that we want to validate
     * @return boolean that indicates whether the number is of a valid pattern
     */
    public function isValidNumber(PhoneNumber $number)
    {
        $regionCode = $this->getRegionCodeForNumber($number);
        return $this->isValidNumberForRegion($number, $regionCode);
    }

    /**
     * Tests whether a phone number is valid for a certain region. Note this doesn't verify the number
     * is actually in use, which is impossible to tell by just looking at a number itself. If the
     * country calling code is not the same as the country calling code for the region, this
     * immediately exits with false. After this, the specific number pattern rules for the region are
     * examined. This is useful for determining for example whether a particular number is valid for
     * Canada, rather than just a valid NANPA number.
     * Warning: In most cases, you want to use {@link #isValidNumber} instead. For example, this
     * method will mark numbers from British Crown dependencies such as the Isle of Man as invalid for
     * the region "GB" (United Kingdom), since it has its own region code, "IM", which may be
     * undesirable.
     *
     * @param PhoneNumber $number the phone number that we want to validate
     * @param string $regionCode the region that we want to validate the phone number for
     * @return boolean that indicates whether the number is of a valid pattern
     */
    public function isValidNumberForRegion(PhoneNumber $number, $regionCode)
    {
        $countryCode = $number->getCountryCode();
        $metadata = $this->getMetadataForRegionOrCallingCode($countryCode, $regionCode);
        if (($metadata === null) ||
            (static::REGION_CODE_FOR_NON_GEO_ENTITY !== $regionCode &&
                $countryCode !== $this->getCountryCodeForValidRegion($regionCode))
        ) {
            // Either the region code was invalid, or the country calling code for this number does not
            // match that of the region code.
            return false;
        }
        $nationalSignificantNumber = $this->getNationalSignificantNumber($number);

        return $this->getNumberTypeHelper($nationalSignificantNumber, $metadata) != PhoneNumberType::UNKNOWN;
    }

    /**
     * Parses a string and returns it as a phone number in proto buffer format. The method is quite
     * lenient and looks for a number in the input text (raw input) and does not check whether the
     * string is definitely only a phone number. To do this, it ignores punctuation and white-space,
     * as well as any text before the number (e.g. a leading “Tel: ”) and trims the non-number bits.
     * It will accept a number in any format (E164, national, international etc), assuming it can
     * interpreted with the defaultRegion supplied. It also attempts to convert any alpha characters
     * into digits if it thinks this is a vanity number of the type "1800 MICROSOFT".
     *
     * <p> This method will throw a {@link NumberParseException} if the number is not considered to
     * be a possible number. Note that validation of whether the number is actually a valid number
     * for a particular region is not performed. This can be done separately with {@link #isValidNumber}.
     *
     * <p> Note this method canonicalizes the phone number such that different representations can be
     * easily compared, no matter what form it was originally entered in (e.g. national,
     * international). If you want to record context about the number being parsed, such as the raw
     * input that was entered, how the country code was derived etc. then call {@link
     * #parseAndKeepRawInput} instead.
     *
     * @param string $numberToParse number that we are attempting to parse. This can contain formatting
     *                          such as +, ( and -, as well as a phone number extension.
     * @param string|null $defaultRegion region that we are expecting the number to be from. This is only used
     *                          if the number being parsed is not written in international format.
     *                          The country_code for the number in this case would be stored as that
     *                          of the default region supplied. If the number is guaranteed to
     *                          start with a '+' followed by the country calling code, then
     *                          "ZZ" or null can be supplied.
     * @param PhoneNumber|null $phoneNumber
     * @param bool $keepRawInput
     * @return PhoneNumber a phone number proto buffer filled with the parsed number
     * @throws NumberParseException  if the string is not considered to be a viable phone number (e.g.
     *                               too few or too many digits) or if no default region was supplied
     *                               and the number is not in international format (does not start
     *                               with +)
     */
    public function parse($numberToParse, $defaultRegion = null, PhoneNumber $phoneNumber = null, $keepRawInput = false)
    {
        if ($phoneNumber === null) {
            $phoneNumber = new PhoneNumber();
        }
        $this->parseHelper($numberToParse, $defaultRegion, $keepRawInput, true, $phoneNumber);
        return $phoneNumber;
    }

    /**
     * Formats a phone number in the specified format using client-defined formatting rules. Note that
     * if the phone number has a country calling code of zero or an otherwise invalid country calling
     * code, we cannot work out things like whether there should be a national prefix applied, or how
     * to format extensions, so we return the national significant number with no formatting applied.
     *
     * @param PhoneNumber $number the phone number to be formatted
     * @param int $numberFormat the format the phone number should be formatted into
     * @param array $userDefinedFormats formatting rules specified by clients
     * @return String the formatted phone number
     */
    public function formatByPattern(PhoneNumber $number, $numberFormat, array $userDefinedFormats)
    {
        $countryCallingCode = $number->getCountryCode();
        $nationalSignificantNumber = $this->getNationalSignificantNumber($number);
        if (!$this->hasValidCountryCallingCode($countryCallingCode)) {
            return $nationalSignificantNumber;
        }
        // Note getRegionCodeForCountryCode() is used because formatting information for regions which
        // share a country calling code is contained by only one region for performance reasons. For
        // example, for NANPA regions it will be contained in the metadata for US.
        $regionCode = $this->getRegionCodeForCountryCode($countryCallingCode);
        // Metadata cannot be null because the country calling code is valid.
        $metadata = $this->getMetadataForRegionOrCallingCode($countryCallingCode, $regionCode);

        $formattedNumber = '';

        $formattingPattern = $this->chooseFormattingPatternForNumber($userDefinedFormats, $nationalSignificantNumber);
        if ($formattingPattern === null) {
            // If no pattern above is matched, we format the number as a whole.
            $formattedNumber .= $nationalSignificantNumber;
        } else {
            $numFormatCopy = new NumberFormat();
            // Before we do a replacement of the national prefix pattern $NP with the national prefix, we
            // need to copy the rule so that subsequent replacements for different numbers have the
            // appropriate national prefix.
            $numFormatCopy->mergeFrom($formattingPattern);
            $nationalPrefixFormattingRule = $formattingPattern->getNationalPrefixFormattingRule();
            if (mb_strlen($nationalPrefixFormattingRule) > 0) {
                $nationalPrefix = $metadata->getNationalPrefix();
                if (mb_strlen($nationalPrefix) > 0) {
                    // Replace $NP with national prefix and $FG with the first group ($1).
                    $nationalPrefixFormattingRule = str_replace(
                        array(static::NP_STRING, static::FG_STRING),
                        array($nationalPrefix, '$1'),
                        $nationalPrefixFormattingRule
                    );
                    $numFormatCopy->setNationalPrefixFormattingRule($nationalPrefixFormattingRule);
                } else {
                    // We don't want to have a rule for how to format the national prefix if there isn't one.
                    $numFormatCopy->clearNationalPrefixFormattingRule();
                }
            }
            $formattedNumber .= $this->formatNsnUsingPattern($nationalSignificantNumber, $numFormatCopy, $numberFormat);
        }
        $this->maybeAppendFormattedExtension($number, $metadata, $numberFormat, $formattedNumber);
        $this->prefixNumberWithCountryCallingCode($countryCallingCode, $numberFormat, $formattedNumber);
        return $formattedNumber;
    }

    /**
     * Gets a valid number for the specified region.
     *
     * @param string regionCode  the region for which an example number is needed
     * @return PhoneNumber a valid fixed-line number for the specified region. Returns null when the metadata
     *    does not contain such information, or the region 001 is passed in. For 001 (representing
     *    non-geographical numbers), call {@link #getExampleNumberForNonGeoEntity} instead.
     */
    public function getExampleNumber($regionCode)
    {
        return $this->getExampleNumberForType($regionCode, PhoneNumberType::FIXED_LINE);
    }

    /**
     * Gets an invalid number for the specified region. This is useful for unit-testing purposes,
     * where you want to test what will happen with an invalid number. Note that the number that is
     * returned will always be able to be parsed and will have the correct country code. It may also
     * be a valid *short* number/code for this region. Validity checking such numbers is handled with
     * {@link ShortNumberInfo}.
     *
     * @param string $regionCode The region for which an example number is needed
     * @return PhoneNumber|null An invalid number for the specified region. Returns null when an unsupported region
     * or the region 001 (Earth) is passed in.
     */
    public function getInvalidExampleNumber($regionCode)
    {
        if (!$this->isValidRegionCode($regionCode)) {
            return null;
        }

        // We start off with a valid fixed-line number since every country supports this. Alternatively
        // we could start with a different number type, since fixed-line numbers typically have a wide
        // breadth of valid number lengths and we may have to make it very short before we get an
        // invalid number.

        $desc = $this->getNumberDescByType($this->getMetadataForRegion($regionCode), PhoneNumberType::FIXED_LINE);

        if ($desc->getExampleNumber() == '') {
            // This shouldn't happen; we have a test for this.
            return null;
        }

        $exampleNumber = $desc->getExampleNumber();

        // Try and make the number invalid. We do this by changing the length. We try reducing the
        // length of the number, since currently no region has a number that is the same length as
        // MIN_LENGTH_FOR_NSN. This is probably quicker than making the number longer, which is another
        // alternative. We could also use the possible number pattern to extract the possible lengths of
        // the number to make this faster, but this method is only for unit-testing so simplicity is
        // preferred to performance.  We don't want to return a number that can't be parsed, so we check
        // the number is long enough. We try all possible lengths because phone number plans often have
        // overlapping prefixes so the number 123456 might be valid as a fixed-line number, and 12345 as
        // a mobile number. It would be faster to loop in a different order, but we prefer numbers that
        // look closer to real numbers (and it gives us a variety of different lengths for the resulting
        // phone numbers - otherwise they would all be MIN_LENGTH_FOR_NSN digits long.)
        for ($phoneNumberLength = mb_strlen($exampleNumber) - 1; $phoneNumberLength >= static::MIN_LENGTH_FOR_NSN; $phoneNumberLength--) {
            $numberToTry = mb_substr($exampleNumber, 0, $phoneNumberLength);
            try {
                $possiblyValidNumber = $this->parse($numberToTry, $regionCode);
                if (!$this->isValidNumber($possiblyValidNumber)) {
                    return $possiblyValidNumber;
                }
            } catch (NumberParseException $e) {
                // Shouldn't happen: we have already checked the length, we know example numbers have
                // only valid digits, and we know the region code is fine.
            }
        }
        // We have a test to check that this doesn't happen for any of our supported regions.
        return null;
    }

    /**
     * Gets a valid number for the specified region and number type.
     *
     * @param string|int $regionCodeOrType the region for which an example number is needed
     * @param int $type the PhoneNumberType of number that is needed
     * @return PhoneNumber|null a valid number for the specified region and type. Returns null when the metadata
     *     does not contain such information or if an invalid region or region 001 was entered.
     *     For 001 (representing non-geographical numbers), call
     *     {@link #getExampleNumberForNonGeoEntity} instead.
     *
     * If $regionCodeOrType is the only parameter supplied, then a valid number for the specified number type
     * will be returned that may belong to any country.
     */
    public function getExampleNumberForType($regionCodeOrType, $type = null)
    {
        if ($regionCodeOrType !== null && $type === null) {
            /*
             * Gets a valid number for the specified number type (it may belong to any country).
             */
            foreach ($this->getSupportedRegions() as $regionCode) {
                $exampleNumber = $this->getExampleNumberForType($regionCode, $regionCodeOrType);
                if ($exampleNumber !== null) {
                    return $exampleNumber;
                }
            }

            // If there wasn't an example number for a region, try the non-geographical entities.
            foreach ($this->getSupportedGlobalNetworkCallingCodes() as $countryCallingCode) {
                $desc = $this->getNumberDescByType($this->getMetadataForNonGeographicalRegion($countryCallingCode), $regionCodeOrType);
                try {
                    if ($desc->getExampleNumber() != '') {
                        return $this->parse('+' . $countryCallingCode . $desc->getExampleNumber(), static::UNKNOWN_REGION);
                    }
                } catch (NumberParseException $e) {
                    // noop
                }
            }
            // There are no example numbers of this type for any country in the library.
            return null;
        }

        // Check the region code is valid.
        if (!$this->isValidRegionCode($regionCodeOrType)) {
            return null;
        }
        $desc = $this->getNumberDescByType($this->getMetadataForRegion($regionCodeOrType), $type);
        try {
            if ($desc->hasExampleNumber()) {
                return $this->parse($desc->getExampleNumber(), $regionCodeOrType);
            }
        } catch (NumberParseException $e) {
            // noop
        }
        return null;
    }

    /**
     * @param PhoneMetadata $metadata
     * @param int $type PhoneNumberType
     * @return PhoneNumberDesc
     */
    protected function getNumberDescByType(PhoneMetadata $metadata, $type)
    {
        switch ($type) {
            case PhoneNumberType::PREMIUM_RATE:
                return $metadata->getPremiumRate();
            case PhoneNumberType::TOLL_FREE:
                return $metadata->getTollFree();
            case PhoneNumberType::MOBILE:
                return $metadata->getMobile();
            case PhoneNumberType::FIXED_LINE:
            case PhoneNumberType::FIXED_LINE_OR_MOBILE:
                return $metadata->getFixedLine();
            case PhoneNumberType::SHARED_COST:
                return $metadata->getSharedCost();
            case PhoneNumberType::VOIP:
                return $metadata->getVoip();
            case PhoneNumberType::PERSONAL_NUMBER:
                return $metadata->getPersonalNumber();
            case PhoneNumberType::PAGER:
                return $metadata->getPager();
            case PhoneNumberType::UAN:
                return $metadata->getUan();
            case PhoneNumberType::VOICEMAIL:
                return $metadata->getVoicemail();
            default:
                return $metadata->getGeneralDesc();
        }
    }

    /**
     * Gets a valid number for the specified country calling code for a non-geographical entity.
     *
     * @param int $countryCallingCode the country calling code for a non-geographical entity
     * @return PhoneNumber a valid number for the non-geographical entity. Returns null when the metadata
     *    does not contain such information, or the country calling code passed in does not belong
     *    to a non-geographical entity.
     */
    public function getExampleNumberForNonGeoEntity($countryCallingCode)
    {
        $metadata = $this->getMetadataForNonGeographicalRegion($countryCallingCode);
        if ($metadata !== null) {
            // For geographical entities, fixed-line data is always present. However, for non-geographical
            // entities, this is not the case, so we have to go through different types to find the
            // example number. We don't check fixed-line or personal number since they aren't used by
            // non-geographical entities (if this changes, a unit-test will catch this.)
            /** @var PhoneNumberDesc[] $list */
            $list = array(
                $metadata->getMobile(),
                $metadata->getTollFree(),
                $metadata->getSharedCost(),
                $metadata->getVoip(),
                $metadata->getVoicemail(),
                $metadata->getUan(),
                $metadata->getPremiumRate(),
            );
            foreach ($list as $desc) {
                try {
                    if ($desc !== null && $desc->hasExampleNumber()) {
                        return $this->parse('+' . $countryCallingCode . $desc->getExampleNumber(), self::UNKNOWN_REGION);
                    }
                } catch (NumberParseException $e) {
                    // noop
                }
            }
        }
        return null;
    }


    /**
     * Takes two phone numbers and compares them for equality.
     *
     * <p>Returns EXACT_MATCH if the country_code, NSN, presence of a leading zero
     * for Italian numbers and any extension present are the same. Returns NSN_MATCH
     * if either or both has no region specified, and the NSNs and extensions are
     * the same. Returns SHORT_NSN_MATCH if either or both has no region specified,
     * or the region specified is the same, and one NSN could be a shorter version
     * of the other number. This includes the case where one has an extension
     * specified, and the other does not. Returns NO_MATCH otherwise. For example,
     * the numbers +1 345 657 1234 and 657 1234 are a SHORT_NSN_MATCH. The numbers
     * +1 345 657 1234 and 345 657 are a NO_MATCH.
     *
     * @param $firstNumberIn PhoneNumber|string First number to compare. If it is a
     * string it can contain formatting, and can have country calling code specified
     * with + at the start.
     * @param $secondNumberIn PhoneNumber|string Second number to compare. If it is a
     * string it can contain formatting, and can have country calling code specified
     * with + at the start.
     * @throws \InvalidArgumentException
     * @return int {MatchType} NOT_A_NUMBER, NO_MATCH,
     */
    public function isNumberMatch($firstNumberIn, $secondNumberIn)
    {
        if (is_string($firstNumberIn) && is_string($secondNumberIn)) {
            try {
                $firstNumberAsProto = $this->parse($firstNumberIn, static::UNKNOWN_REGION);
                return $this->isNumberMatch($firstNumberAsProto, $secondNumberIn);
            } catch (NumberParseException $e) {
                if ($e->getErrorType() === NumberParseException::INVALID_COUNTRY_CODE) {
                    try {
                        $secondNumberAsProto = $this->parse($secondNumberIn, static::UNKNOWN_REGION);
                        return $this->isNumberMatch($secondNumberAsProto, $firstNumberIn);
                    } catch (NumberParseException $e2) {
                        if ($e2->getErrorType() === NumberParseException::INVALID_COUNTRY_CODE) {
                            try {
                                $firstNumberProto = new PhoneNumber();
                                $secondNumberProto = new PhoneNumber();
                                $this->parseHelper($firstNumberIn, null, false, false, $firstNumberProto);
                                $this->parseHelper($secondNumberIn, null, false, false, $secondNumberProto);
                                return $this->isNumberMatch($firstNumberProto, $secondNumberProto);
                            } catch (NumberParseException $e3) {
                                // Fall through and return MatchType::NOT_A_NUMBER
                            }
                        }
                    }
                }
            }
            return MatchType::NOT_A_NUMBER;
        }
        if ($firstNumberIn instanceof PhoneNumber && is_string($secondNumberIn)) {
            // First see if the second number has an implicit country calling code, by attempting to parse
            // it.
            try {
                $secondNumberAsProto = $this->parse($secondNumberIn, static::UNKNOWN_REGION);
                return $this->isNumberMatch($firstNumberIn, $secondNumberAsProto);
            } catch (NumberParseException $e) {
                if ($e->getErrorType() === NumberParseException::INVALID_COUNTRY_CODE) {
                    // The second number has no country calling code. EXACT_MATCH is no longer possible.
                    // We parse it as if the region was the same as that for the first number, and if
                    // EXACT_MATCH is returned, we replace this with NSN_MATCH.
                    $firstNumberRegion = $this->getRegionCodeForCountryCode($firstNumberIn->getCountryCode());
                    try {
                        if ($firstNumberRegion != static::UNKNOWN_REGION) {
                            $secondNumberWithFirstNumberRegion = $this->parse($secondNumberIn, $firstNumberRegion);
                            $match = $this->isNumberMatch($firstNumberIn, $secondNumberWithFirstNumberRegion);
                            if ($match === MatchType::EXACT_MATCH) {
                                return MatchType::NSN_MATCH;
                            }
                            return $match;
                        }

                        // If the first number didn't have a valid country calling code, then we parse the
                        // second number without one as well.
                        $secondNumberProto = new PhoneNumber();
                        $this->parseHelper($secondNumberIn, null, false, false, $secondNumberProto);
                        return $this->isNumberMatch($firstNumberIn, $secondNumberProto);
                    } catch (NumberParseException $e2) {
                        // Fall-through to return NOT_A_NUMBER.
                    }
                }
            }
        }
        if ($firstNumberIn instanceof PhoneNumber && $secondNumberIn instanceof PhoneNumber) {
            // We only care about the fields that uniquely define a number, so we copy these across
            // explicitly.
            $firstNumber = self::copyCoreFieldsOnly($firstNumberIn);
            $secondNumber = self::copyCoreFieldsOnly($secondNumberIn);

            // Early exit if both had extensions and these are different.
            if ($firstNumber->hasExtension() && $secondNumber->hasExtension() &&
                $firstNumber->getExtension() != $secondNumber->getExtension()
            ) {
                return MatchType::NO_MATCH;
            }

            $firstNumberCountryCode = $firstNumber->getCountryCode();
            $secondNumberCountryCode = $secondNumber->getCountryCode();
            // Both had country_code specified.
            if ($firstNumberCountryCode != 0 && $secondNumberCountryCode != 0) {
                if ($firstNumber->equals($secondNumber)) {
                    return MatchType::EXACT_MATCH;
                }

                if ($firstNumberCountryCode == $secondNumberCountryCode &&
                    $this->isNationalNumberSuffixOfTheOther($firstNumber, $secondNumber)) {
                    // A SHORT_NSN_MATCH occurs if there is a difference because of the presence or absence of
                    // an 'Italian leading zero', the presence or absence of an extension, or one NSN being a
                    // shorter variant of the other.
                    return MatchType::SHORT_NSN_MATCH;
                }
                // This is not a match.
                return MatchType::NO_MATCH;
            }
            // Checks cases where one or both country_code fields were not specified. To make equality
            // checks easier, we first set the country_code fields to be equal.
            $firstNumber->setCountryCode($secondNumberCountryCode);
            // If all else was the same, then this is an NSN_MATCH.
            if ($firstNumber->equals($secondNumber)) {
                return MatchType::NSN_MATCH;
            }
            if ($this->isNationalNumberSuffixOfTheOther($firstNumber, $secondNumber)) {
                return MatchType::SHORT_NSN_MATCH;
            }
            return MatchType::NO_MATCH;
        }
        return MatchType::NOT_A_NUMBER;
    }

    /**
     * Returns true when one national number is the suffix of the other or both are the same.
     * @param PhoneNumber $firstNumber
     * @param PhoneNumber $secondNumber
     * @return bool
     */
    protected function isNationalNumberSuffixOfTheOther(PhoneNumber $firstNumber, PhoneNumber $secondNumber)
    {
        $firstNumberNationalNumber = trim((string)$firstNumber->getNationalNumber());
        $secondNumberNationalNumber = trim((string)$secondNumber->getNationalNumber());
        return $this->stringEndsWithString($firstNumberNationalNumber, $secondNumberNationalNumber) ||
        $this->stringEndsWithString($secondNumberNationalNumber, $firstNumberNationalNumber);
    }

    /**
     * Returns true if a string ends with a given substring, false otherwise.
     *
     * @param string $hayStack
     * @param string $needle
     * @return bool
     */
    protected function stringEndsWithString($hayStack, $needle)
    {
        $revNeedle = strrev($needle);
        $revHayStack = strrev($hayStack);
        return strpos($revHayStack, $revNeedle) === 0;
    }

    /**
     * Returns true if the supplied region supports mobile number portability. Returns false for
     * invalid, unknown or regions that don't support mobile number portability.
     *
     * @param string $regionCode the region for which we want to know whether it supports mobile number
     *                    portability or not.
     * @return bool
     */
    public function isMobileNumberPortableRegion($regionCode)
    {
        $metadata = $this->getMetadataForRegion($regionCode);
        if ($metadata === null) {
            return false;
        }

        return $metadata->isMobileNumberPortableRegion();
    }

    /**
     * Check whether a phone number is a possible number given a number in the form of a string, and
     * the region where the number could be dialed from. It provides a more lenient check than
     * {@link #isValidNumber}. See {@link #isPossibleNumber(PhoneNumber)} for details.
     *
     * Convenience wrapper around {@link #isPossibleNumberWithReason}. Instead of returning the reason
     * for failure, this method returns a boolean value.
     * For failure, this method returns true if the number is either a possible fully-qualified number
     * (containing the area code and country code), or if the number could be a possible local number
     * (with a country code, but missing an area code). Local numbers are considered possible if they
     * could be possibly dialled in this format: if the area code is needed for a call to connect, the
     * number is not considered possible without it.
     *
     * Note: There are two ways to call this method.
     *
     * isPossibleNumber(PhoneNumber $numberObject)
     * isPossibleNumber(string '+441174960126', string 'GB')
     *
     * @param PhoneNumber|string $number the number that needs to be checked, in the form of a string
     * @param string|null $regionDialingFrom the region that we are expecting the number to be dialed from.
     *     Note this is different from the region where the number belongs.  For example, the number
     *     +1 650 253 0000 is a number that belongs to US. When written in this form, it can be
     *     dialed from any region. When it is written as 00 1 650 253 0000, it can be dialed from any
     *     region which uses an international dialling prefix of 00. When it is written as
     *     650 253 0000, it can only be dialed from within the US, and when written as 253 0000, it
     *     can only be dialed from within a smaller area in the US (Mountain View, CA, to be more
     *     specific).
     * @return boolean true if the number is possible
     */
    public function isPossibleNumber($number, $regionDialingFrom = null)
    {
        if (is_string($number)) {
            try {
                return $this->isPossibleNumber($this->parse($number, $regionDialingFrom));
            } catch (NumberParseException $e) {
                return false;
            }
        } else {
            $result = $this->isPossibleNumberWithReason($number);
            return $result === ValidationResult::IS_POSSIBLE
                || $result === ValidationResult::IS_POSSIBLE_LOCAL_ONLY;
        }
    }


    /**
     * Check whether a phone number is a possible number. It provides a more lenient check than
     * {@link #isValidNumber} in the following sense:
     * <ol>
     *   <li> It only checks the length of phone numbers. In particular, it doesn't check starting
     *        digits of the number.
     *   <li> It doesn't attempt to figure out the type of the number, but uses general rules which
     *        applies to all types of phone numbers in a region. Therefore, it is much faster than
     *        isValidNumber.
     *   <li> For some numbers (particularly fixed-line), many regions have the concept of area code,
     *        which together with subscriber number constitute the national significant number. It is
     *        sometimes okay to dial only the subscriber number when dialing in the same area. This
     *        function will return IS_POSSIBLE_LOCAL_ONLY if the subscriber-number-only version is
     *        passed in. On the other hand, because isValidNumber validates using information on both
     *        starting digits (for fixed line numbers, that would most likely be area codes) and
     *        length (obviously includes the length of area codes for fixed line numbers), it will
     *        return false for the subscriber-number-only version.
     * </ol>
     * @param PhoneNumber $number the number that needs to be checked
     * @return int a ValidationResult object which indicates whether the number is possible
     */
    public function isPossibleNumberWithReason(PhoneNumber $number)
    {
        return $this->isPossibleNumberForTypeWithReason($number, PhoneNumberType::UNKNOWN);
    }

    /**
     * Check whether a phone number is a possible number of a particular type. For types that don't
     * exist in a particular region, this will return a result that isn't so useful; it is recommended
     * that you use {@link #getSupportedTypesForRegion} or {@link #getSupportedTypesForNonGeoEntity}
     * respectively before calling this method to determine whether you should call it for this number
     * at all.
     *
     * This provides a more lenient check than {@link #isValidNumber} in the following sense:
     *
     * <ol>
     *   <li> It only checks the length of phone numbers. In particular, it doesn't check starting
     *        digits of the number.
     *   <li> For some numbers (particularly fixed-line), many regions have the concept of area code,
     *        which together with subscriber number constitute the national significant number. It is
     *        sometimes okay to dial only the subscriber number when dialing in the same area. This
     *        function will return IS_POSSIBLE_LOCAL_ONLY if the subscriber-number-only version is
     *        passed in. On the other hand, because isValidNumber validates using information on both
     *        starting digits (for fixed line numbers, that would most likely be area codes) and
     *        length (obviously includes the length of area codes for fixed line numbers), it will
     *        return false for the subscriber-number-only version.
     * </ol>
     *
     * @param PhoneNumber $number the number that needs to be checked
     * @param int $type the PhoneNumberType we are interested in
     * @return int a ValidationResult object which indicates whether the number is possible
     */
    public function isPossibleNumberForTypeWithReason(PhoneNumber $number, $type)
    {
        $nationalNumber = $this->getNationalSignificantNumber($number);
        $countryCode = $number->getCountryCode();

        // Note: For regions that share a country calling code, like NANPA numbers, we just use the
        // rules from the default region (US in this case) since the getRegionCodeForNumber will not
        // work if the number is possible but not valid. There is in fact one country calling code (290)
        // where the possible number pattern differs between various regions (Saint Helena and Tristan
        // da Cuñha), but this is handled by putting all possible lengths for any country with this
        // country calling code in the metadata for the default region in this case.
        if (!$this->hasValidCountryCallingCode($countryCode)) {
            return ValidationResult::INVALID_COUNTRY_CODE;
        }

        $regionCode = $this->getRegionCodeForCountryCode($countryCode);
        // Metadata cannot be null because the country calling code is valid.
        $metadata = $this->getMetadataForRegionOrCallingCode($countryCode, $regionCode);
        return $this->testNumberLength($nationalNumber, $metadata, $type);
    }

    /**
     * Attempts to extract a valid number from a phone number that is too long to be valid, and resets
     * the PhoneNumber object passed in to that valid version. If no valid number could be extracted,
     * the PhoneNumber object passed in will not be modified.
     *
     * @param PhoneNumber $number a PhoneNumber object which contains a number that is too long to be valid.
     * @return boolean true if a valid phone number can be successfully extracted.
     */
    public function truncateTooLongNumber(PhoneNumber $number)
    {
        if ($this->isValidNumber($number)) {
            return true;
        }
        $numberCopy = new PhoneNumber();
        $numberCopy->mergeFrom($number);
        $nationalNumber = $number->getNationalNumber();
        do {
            $nationalNumber = floor($nationalNumber / 10);
            $numberCopy->setNationalNumber($nationalNumber);
            if ($this->isPossibleNumberWithReason($numberCopy) == ValidationResult::TOO_SHORT || $nationalNumber == 0) {
                return false;
            }
        } while (!$this->isValidNumber($numberCopy));
        $number->setNationalNumber($nationalNumber);
        return true;
    }
}
