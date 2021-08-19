<?php

namespace libphonenumber;

/**
 * Class RegexBasedMatcher
 * @package libphonenumber
 * @internal
 */
class RegexBasedMatcher implements MatcherAPIInterface
{
    public static function create()
    {
        return new static();
    }

    /**
     * Returns whether the given national number (a string containing only decimal digits) matches
     * the national number pattern defined in the given {@code PhoneNumberDesc} message.
     *
     * @param string $number
     * @param PhoneNumberDesc $numberDesc
     * @param boolean $allowPrefixMatch
     * @return boolean
     */
    public function matchNationalNumber($number, PhoneNumberDesc $numberDesc, $allowPrefixMatch)
    {
        $nationalNumberPattern = $numberDesc->getNationalNumberPattern();

        // We don't want to consider it a prefix match when matching non-empty input against an empty
        // pattern

        if (\strlen($nationalNumberPattern) === 0) {
            return false;
        }

        return $this->match($number, $nationalNumberPattern, $allowPrefixMatch);
    }

    /**
     * @param string $number
     * @param string $pattern
     * @param $allowPrefixMatch
     * @return bool
     */
    private function match($number, $pattern, $allowPrefixMatch)
    {
        $matcher = new Matcher($pattern, $number);

        if (!$matcher->lookingAt()) {
            return false;
        }

        return $matcher->matches() ? true : $allowPrefixMatch;
    }
}
