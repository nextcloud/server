<?php

namespace Masterminds\HTML5\Parser;

/*
Portions based on code from html5lib files with the following copyright:

Copyright 2009 Geoffrey Sneddon <http://gsnedders.com/>

Permission is hereby granted, free of charge, to any person obtaining a
copy of this software and associated documentation files (the
    "Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

use Masterminds\HTML5\Exception;

class UTF8Utils
{
    /**
     * The Unicode replacement character.
     */
    const FFFD = "\xEF\xBF\xBD";

    /**
     * Count the number of characters in a string.
     * UTF-8 aware. This will try (in order) iconv, MB, and finally a custom counter.
     *
     * @param string $string
     *
     * @return int
     */
    public static function countChars($string)
    {
        // Get the length for the string we need.
        if (function_exists('mb_strlen')) {
            return mb_strlen($string, 'utf-8');
        }

        if (function_exists('iconv_strlen')) {
            return iconv_strlen($string, 'utf-8');
        }

        $count = count_chars($string);

        // 0x80 = 0x7F - 0 + 1 (one added to get inclusive range)
        // 0x33 = 0xF4 - 0x2C + 1 (one added to get inclusive range)
        return array_sum(array_slice($count, 0, 0x80)) + array_sum(array_slice($count, 0xC2, 0x33));
    }

    /**
     * Convert data from the given encoding to UTF-8.
     *
     * This has not yet been tested with charactersets other than UTF-8.
     * It should work with ISO-8859-1/-13 and standard Latin Win charsets.
     *
     * @param string $data     The data to convert
     * @param string $encoding A valid encoding. Examples: http://www.php.net/manual/en/mbstring.supported-encodings.php
     *
     * @return string
     */
    public static function convertToUTF8($data, $encoding = 'UTF-8')
    {
        /*
         * From the HTML5 spec: Given an encoding, the bytes in the input stream must be converted
         * to Unicode characters for the tokeniser, as described by the rules for that encoding,
         * except that the leading U+FEFF BYTE ORDER MARK character, if any, must not be stripped
         * by the encoding layer (it is stripped by the rule below). Bytes or sequences of bytes
         * in the original byte stream that could not be converted to Unicode characters must be
         * converted to U+FFFD REPLACEMENT CHARACTER code points.
         */

        // mb_convert_encoding is chosen over iconv because of a bug. The best
        // details for the bug are on http://us1.php.net/manual/en/function.iconv.php#108643
        // which contains links to the actual but reports as well as work around
        // details.
        if (function_exists('mb_convert_encoding')) {
            // mb library has the following behaviors:
            // - UTF-16 surrogates result in false.
            // - Overlongs and outside Plane 16 result in empty strings.

            // Before we run mb_convert_encoding we need to tell it what to do with
            // characters it does not know. This could be different than the parent
            // application executing this library so we store the value, change it
            // to our needs, and then change it back when we are done. This feels
            // a little excessive and it would be great if there was a better way.
            $save = mb_substitute_character();
            mb_substitute_character('none');
            $data = mb_convert_encoding($data, 'UTF-8', $encoding);
            mb_substitute_character($save);
        }
        // @todo Get iconv running in at least some environments if that is possible.
        elseif (function_exists('iconv') && 'auto' !== $encoding) {
            // fprintf(STDOUT, "iconv found\n");
            // iconv has the following behaviors:
            // - Overlong representations are ignored.
            // - Beyond Plane 16 is replaced with a lower char.
            // - Incomplete sequences generate a warning.
            $data = @iconv($encoding, 'UTF-8//IGNORE', $data);
        } else {
            throw new Exception('Not implemented, please install mbstring or iconv');
        }

        /*
         * One leading U+FEFF BYTE ORDER MARK character must be ignored if any are present.
         */
        if ("\xEF\xBB\xBF" === substr($data, 0, 3)) {
            $data = substr($data, 3);
        }

        return $data;
    }

    /**
     * Checks for Unicode code points that are not valid in a document.
     *
     * @param string $data A string to analyze
     *
     * @return array An array of (string) error messages produced by the scanning
     */
    public static function checkForIllegalCodepoints($data)
    {
        // Vestigal error handling.
        $errors = array();

        /*
         * All U+0000 null characters in the input must be replaced by U+FFFD REPLACEMENT CHARACTERs.
         * Any occurrences of such characters is a parse error.
         */
        for ($i = 0, $count = substr_count($data, "\0"); $i < $count; ++$i) {
            $errors[] = 'null-character';
        }

        /*
         * Any occurrences of any characters in the ranges U+0001 to U+0008, U+000B, U+000E to U+001F, U+007F
         * to U+009F, U+D800 to U+DFFF , U+FDD0 to U+FDEF, and characters U+FFFE, U+FFFF, U+1FFFE, U+1FFFF,
         * U+2FFFE, U+2FFFF, U+3FFFE, U+3FFFF, U+4FFFE, U+4FFFF, U+5FFFE, U+5FFFF, U+6FFFE, U+6FFFF, U+7FFFE,
         * U+7FFFF, U+8FFFE, U+8FFFF, U+9FFFE, U+9FFFF, U+AFFFE, U+AFFFF, U+BFFFE, U+BFFFF, U+CFFFE, U+CFFFF,
         * U+DFFFE, U+DFFFF, U+EFFFE, U+EFFFF, U+FFFFE, U+FFFFF, U+10FFFE, and U+10FFFF are parse errors.
         * (These are all control characters or permanently undefined Unicode characters.)
         */
        // Check PCRE is loaded.
        $count = preg_match_all(
            '/(?:
        [\x01-\x08\x0B\x0E-\x1F\x7F] # U+0001 to U+0008, U+000B,  U+000E to U+001F and U+007F
      |
        \xC2[\x80-\x9F] # U+0080 to U+009F
      |
        \xED(?:\xA0[\x80-\xFF]|[\xA1-\xBE][\x00-\xFF]|\xBF[\x00-\xBF]) # U+D800 to U+DFFFF
      |
        \xEF\xB7[\x90-\xAF] # U+FDD0 to U+FDEF
      |
        \xEF\xBF[\xBE\xBF] # U+FFFE and U+FFFF
      |
        [\xF0-\xF4][\x8F-\xBF]\xBF[\xBE\xBF] # U+nFFFE and U+nFFFF (1 <= n <= 10_{16})
      )/x', $data, $matches);
        for ($i = 0; $i < $count; ++$i) {
            $errors[] = 'invalid-codepoint';
        }

        return $errors;
    }
}
