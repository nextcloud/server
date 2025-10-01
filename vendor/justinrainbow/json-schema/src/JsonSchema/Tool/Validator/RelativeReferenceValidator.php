<?php

declare(strict_types=1);

namespace JsonSchema\Tool\Validator;

class RelativeReferenceValidator
{
    public static function isValid(string $ref): bool
    {
        // Relative reference pattern as per RFC 3986, Section 4.1
        $pattern = '/^(([^\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?$/';

        if (preg_match($pattern, $ref) !== 1) {
            return false;
        }

        // Additional checks for invalid cases
        if (preg_match('/^(http|https):\/\//', $ref)) {
            return false; // Absolute URI
        }

        if (preg_match('/^:\/\//', $ref)) {
            return false; // Missing scheme in authority
        }

        if (preg_match('/^:\//', $ref)) {
            return false; // Invalid scheme separator
        }

        if (preg_match('/^\/\/$/', $ref)) {
            return false; // Empty authority
        }

        if (preg_match('/^\/\/\/[^\/]/', $ref)) {
            return false; // Invalid authority with three slashes
        }

        if (preg_match('/\s/', $ref)) {
            return false; // Spaces are not allowed in URIs
        }

        if (preg_match('/^\?#|^#$/', $ref)) {
            return false; // Missing path but having query and fragment
        }

        if ($ref === '#' || $ref === '?') {
            return false; // Missing path and having only fragment or query
        }

        return true;
    }
}
