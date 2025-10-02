<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\StringPrep;

/**
 * Implements 'Prohibit' step of the Internationalized String Preparation as specified by RFC 4518.
 *
 * @see https://tools.ietf.org/html/rfc4518#section-2.4
 */
final class ProhibitStep implements PrepareStep
{
    /**
     * @param string $string UTF-8 encoded string
     */
    public function apply(string $string): string
    {
        // @todo Implement
        return $string;
    }
}
