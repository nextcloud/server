<?php

declare(strict_types=1);

namespace libphonenumber;

/**
 * Interface MatcherAPIInterface
 *
 * Internal phonenumber matching API used to isolate the underlying implementation of the
 * matcher and allow different implementations to be swapped in easily.
 *
 * @package libphonenumber
 * @internal
 */
interface MatcherAPIInterface
{
    /**
     * Returns whether the given national number (a string containing only decimal digits) matches
     * the national number pattern defined in the given {@code PhoneNumberDesc} message.
     */
    public function matchNationalNumber(string $number, PhoneNumberDesc $numberDesc, bool $allowPrefixMatch): bool;
}
