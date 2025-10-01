<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\StringPrep;

/**
 * Implements 'Insignificant Space Handling' step of the Internationalized String Preparation as specified by RFC 4518.
 *
 * This variant handles input strings that are non-substring assertion values.
 *
 * @see https://tools.ietf.org/html/rfc4518#section-2.6.1
 */
final class InsignificantNonSubstringSpaceStep implements PrepareStep
{
    /**
     * @param string $string UTF-8 encoded string
     */
    public function apply(string $string): string
    {
        // if value contains no non-space characters
        if (preg_match('/^\p{Zs}*$/u', $string) === 1) {
            return '  ';
        }
        // trim leading and trailing spaces
        $string = preg_replace('/^\p{Zs}+/u', '', $string);
        $string = preg_replace('/\p{Zs}+$/u', '', (string) $string);
        // convert inner space sequences to two U+0020 characters
        $string = preg_replace('/\p{Zs}+/u', '  ', (string) $string);
        return " {$string} ";
    }
}
