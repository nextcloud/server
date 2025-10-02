<?php

namespace Sabre\VObject;

/**
 * Useful utilities for working with various strings.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class StringUtil
{
    /**
     * Returns true or false depending on if a string is valid UTF-8.
     *
     * @param string $str
     *
     * @return bool
     */
    public static function isUTF8($str)
    {
        // Control characters
        if (preg_match('%[\x00-\x08\x0B-\x0C\x0E\x0F]%', $str)) {
            return false;
        }

        return (bool) preg_match('%%u', $str);
    }

    /**
     * This method tries its best to convert the input string to UTF-8.
     *
     * Currently only ISO-5991-1 input and UTF-8 input is supported, but this
     * may be expanded upon if we receive other examples.
     *
     * @param string $str
     *
     * @return string
     */
    public static function convertToUTF8($str)
    {
        if (!mb_check_encoding($str, 'UTF-8') && mb_check_encoding($str, 'ISO-8859-1')) {
            $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }

        // Removing any control characters
        return preg_replace('%(?:[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F])%', '', $str);
    }
}
