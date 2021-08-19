<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * String utility.
 *
 * This class is mainly used to implement the 'text-match' filter, used by both
 * the CalDAV calendar-query REPORT, and CardDAV addressbook-query REPORT.
 * Because they both need it, it was decided to put it in Sabre\DAV instead.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class StringUtil
{
    /**
     * Checks if a needle occurs in a haystack ;).
     *
     * @param string $haystack
     * @param string $needle
     * @param string $collation
     * @param string $matchType
     *
     * @return bool
     */
    public static function textMatch($haystack, $needle, $collation, $matchType = 'contains')
    {
        switch ($collation) {
            case 'i;ascii-casemap':
                // default strtolower takes locale into consideration
                // we don't want this.
                $haystack = str_replace(range('a', 'z'), range('A', 'Z'), $haystack);
                $needle = str_replace(range('a', 'z'), range('A', 'Z'), $needle);
                break;

            case 'i;octet':
                // Do nothing
                break;

            case 'i;unicode-casemap':
                $haystack = mb_strtoupper($haystack, 'UTF-8');
                $needle = mb_strtoupper($needle, 'UTF-8');
                break;

            default:
                throw new Exception\BadRequest('Collation type: '.$collation.' is not supported');
        }

        switch ($matchType) {
            case 'contains':
                return false !== strpos($haystack, $needle);
            case 'equals':
                return $haystack === $needle;
            case 'starts-with':
                return 0 === strpos($haystack, $needle);
            case 'ends-with':
                return strrpos($haystack, $needle) === strlen($haystack) - strlen($needle);
            default:
                throw new Exception\BadRequest('Match-type: '.$matchType.' is not supported');
        }
    }

    /**
     * This method takes an input string, checks if it's not valid UTF-8 and
     * attempts to convert it to UTF-8 if it's not.
     *
     * Note that currently this can only convert ISO-8859-1 to UTF-8 (latin-1),
     * anything else will likely fail.
     *
     * @param string $input
     *
     * @return string
     */
    public static function ensureUTF8($input)
    {
        $encoding = mb_detect_encoding($input, ['UTF-8', 'ISO-8859-1'], true);

        if ('ISO-8859-1' === $encoding) {
            return utf8_encode($input);
        } else {
            return $input;
        }
    }
}
