<?php

declare(strict_types=1);

namespace libphonenumber;

/**
 * Class RegexBasedMatcher
 * @package libphonenumber
 * @internal
 */
class RegexBasedMatcher implements MatcherAPIInterface
{
    /**
     * Returns whether the given national number (a string containing only decimal digits) matches
     * the national number pattern defined in the given {@code PhoneNumberDesc} message.
     */
    public function matchNationalNumber(string $number, PhoneNumberDesc $numberDesc, bool $allowPrefixMatch): bool
    {
        $nationalNumberPattern = $numberDesc->getNationalNumberPattern();

        // We don't want to consider it a prefix match when matching non-empty input against an empty
        // pattern

        if ($nationalNumberPattern === '') {
            return false;
        }

        return $this->match($number, $nationalNumberPattern, $allowPrefixMatch);
    }

    private function match(string $number, string $pattern, bool $allowPrefixMatch): bool
    {
        $matcher = new Matcher($pattern, $number);

        if (!$matcher->lookingAt()) {
            return false;
        }

        return $matcher->matches() ? true : $allowPrefixMatch;
    }
}
