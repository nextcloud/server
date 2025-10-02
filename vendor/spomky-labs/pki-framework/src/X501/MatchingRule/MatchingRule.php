<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\MatchingRule;

/**
 * Base class for attribute matching rules.
 *
 * @see https://tools.ietf.org/html/rfc4517#section-4
 */
abstract class MatchingRule
{
    /**
     * Compare attribute value to assertion.
     *
     * @param string $assertion Value to assert
     * @param string $value Attribute value
     *
     * @return null|bool True if value matches. Null shall be returned if match
     * evaluates to Undefined.
     */
    abstract public function compare(string $assertion, string $value): ?bool;
}
