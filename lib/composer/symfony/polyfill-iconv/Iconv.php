<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Iconv;

/**
 * iconv implementation in pure PHP, UTF-8 centric.
 *
 * Implemented:
 * - iconv              - Convert string to requested character encoding
 * - iconv_mime_decode  - Decodes a MIME header field
 * - iconv_mime_decode_headers - Decodes multiple MIME header fields at once
 * - iconv_get_encoding - Retrieve internal configuration variables of iconv extension
 * - iconv_set_encoding - Set current setting for character encoding conversion
 * - iconv_mime_encode  - Composes a MIME header field
 * - iconv_strlen       - Returns the character count of string
 * - iconv_strpos       - Finds position of first occurrence of a needle within a haystack
 * - iconv_strrpos      - Finds the last occurrence of a needle within a haystack
 * - iconv_substr       - Cut out part of a string
 *
 * Charsets available for conversion are defined by files
 * in the charset/ directory and by Iconv::$alias below.
 * You're welcome to send back any addition you make.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
final class Iconv
{
    public const ERROR_ILLEGAL_CHARACTER = 'iconv(): Detected an illegal character in input string';
    public const ERROR_WRONG_CHARSET = 'iconv(): Wrong charset, conversion from `%s\' to `%s\' is not allowed';

    public static $inputEncoding = 'utf-8';
    public static $outputEncoding = 'utf-8';
    public static $internalEncoding = 'utf-8';

    private static $alias = [
        'utf8' => 'utf-8',
        'ascii' => 'us-ascii',
        'tis-620' => 'iso-8859-11',
        'cp1250' => 'windows-1250',
        'cp1251' => 'windows-1251',
        'cp1252' => 'windows-1252',
        'cp1253' => 'windows-1253',
        'cp1254' => 'windows-1254',
        'cp1255' => 'windows-1255',
        'cp1256' => 'windows-1256',
        'cp1257' => 'windows-1257',
        'cp1258' => 'windows-1258',
        'shift-jis' => 'cp932',
        'shift_jis' => 'cp932',
        'latin1' => 'iso-8859-1',
        'latin2' => 'iso-8859-2',
        'latin3' => 'iso-8859-3',
        'latin4' => 'iso-8859-4',
        'latin5' => 'iso-8859-9',
        'latin6' => 'iso-8859-10',
        'latin7' => 'iso-8859-13',
        'latin8' => 'iso-8859-14',
        'latin9' => 'iso-8859-15',
        'latin10' => 'iso-8859-16',
        'iso8859-1' => 'iso-8859-1',
        'iso8859-2' => 'iso-8859-2',
        'iso8859-3' => 'iso-8859-3',
        'iso8859-4' => 'iso-8859-4',
        'iso8859-5' => 'iso-8859-5',
        'iso8859-6' => 'iso-8859-6',
        'iso8859-7' => 'iso-8859-7',
        'iso8859-8' => 'iso-8859-8',
        'iso8859-9' => 'iso-8859-9',
        'iso8859-10' => 'iso-8859-10',
        'iso8859-11' => 'iso-8859-11',
        'iso8859-12' => 'iso-8859-12',
        'iso8859-13' => 'iso-8859-13',
        'iso8859-14' => 'iso-8859-14',
        'iso8859-15' => 'iso-8859-15',
        'iso8859-16' => 'iso-8859-16',
        'iso_8859-1' => 'iso-8859-1',
        'iso_8859-2' => 'iso-8859-2',
        'iso_8859-3' => 'iso-8859-3',
        'iso_8859-4' => 'iso-8859-4',
        'iso_8859-5' => 'iso-8859-5',
        'iso_8859-6' => 'iso-8859-6',
        'iso_8859-7' => 'iso-8859-7',
        'iso_8859-8' => 'iso-8859-8',
        'iso_8859-9' => 'iso-8859-9',
        'iso_8859-10' => 'iso-8859-10',
        'iso_8859-11' => 'iso-8859-11',
        'iso_8859-12' => 'iso-8859-12',
        'iso_8859-13' => 'iso-8859-13',
        'iso_8859-14' => 'iso-8859-14',
        'iso_8859-15' => 'iso-8859-15',
        'iso_8859-16' => 'iso-8859-16',
        'iso88591' => 'iso-8859-1',
        'iso88592' => 'iso-8859-2',
        'iso88593' => 'iso-8859-3',
        'iso88594' => 'iso-8859-4',
        'iso88595' => 'iso-8859-5',
        'iso88596' => 'iso-8859-6',
        'iso88597' => 'iso-8859-7',
        'iso88598' => 'iso-8859-8',
        'iso88599' => 'iso-8859-9',
        'iso885910' => 'iso-8859-10',
        'iso885911' => 'iso-8859-11',
        'iso885912' => 'iso-8859-12',
        'iso885913' => 'iso-8859-13',
        'iso885914' => 'iso-8859-14',
        'iso885915' => 'iso-8859-15',
        'iso885916' => 'iso-8859-16',
    ];
    private static $translitMap = [];
    private static $convertMap = [];
    private static $errorHandler;
    private static $lastError;

    private static $ulenMask = ["\xC0" => 2, "\xD0" => 2, "\xE0" => 3, "\xF0" => 4];
    private static $isValidUtf8;

    public static function iconv($inCharset, $outCharset, $str)
    {
        $str = (string) $str;
        if ('' === $str) {
            return '';
        }

        // Prepare for //IGNORE and //TRANSLIT

        $translit = $ignore = '';

        $outCharset = strtolower($outCharset);
        $inCharset = strtolower($inCharset);

        if ('' === $outCharset) {
            $outCharset = 'iso-8859-1';
        }
        if ('' === $inCharset) {
            $inCharset = 'iso-8859-1';
        }

        do {
            $loop = false;

            if ('//translit' === substr($outCharset, -10)) {
                $loop = $translit = true;
                $outCharset = substr($outCharset, 0, -10);
            }

            if ('//ignore' === substr($outCharset, -8)) {
                $loop = $ignore = true;
                $outCharset = substr($outCharset, 0, -8);
            }
        } while ($loop);

        do {
            $loop = false;

            if ('//translit' === substr($inCharset, -10)) {
                $loop = true;
                $inCharset = substr($inCharset, 0, -10);
            }

            if ('//ignore' === substr($inCharset, -8)) {
                $loop = true;
                $inCharset = substr($inCharset, 0, -8);
            }
        } while ($loop);

        if (isset(self::$alias[$inCharset])) {
            $inCharset = self::$alias[$inCharset];
        }
        if (isset(self::$alias[$outCharset])) {
            $outCharset = self::$alias[$outCharset];
        }

        // Load charset maps

        if (('utf-8' !== $inCharset && !self::loadMap('from.', $inCharset, $inMap))
          || ('utf-8' !== $outCharset && !self::loadMap('to.', $outCharset, $outMap))) {
            trigger_error(sprintf(self::ERROR_WRONG_CHARSET, $inCharset, $outCharset));

            return false;
        }

        if ('utf-8' !== $inCharset) {
            // Convert input to UTF-8
            $result = '';
            if (self::mapToUtf8($result, $inMap, $str, $ignore)) {
                $str = $result;
            } else {
                $str = false;
            }
            self::$isValidUtf8 = true;
        } else {
            self::$isValidUtf8 = preg_match('//u', $str);

            if (!self::$isValidUtf8 && !$ignore) {
                trigger_error(self::ERROR_ILLEGAL_CHARACTER);

                return false;
            }

            if ('utf-8' === $outCharset) {
                // UTF-8 validation
                $str = self::utf8ToUtf8($str, $ignore);
            }
        }

        if ('utf-8' !== $outCharset && false !== $str) {
            // Convert output to UTF-8
            $result = '';
            if (self::mapFromUtf8($result, $outMap, $str, $ignore, $translit)) {
                return $result;
            }

            return false;
        }

        return $str;
    }

    public static function iconv_mime_decode_headers($str, $mode = 0, $charset = null)
    {
        if (null === $charset) {
            $charset = self::$internalEncoding;
        }

        if (false !== strpos($str, "\r")) {
            $str = strtr(str_replace("\r\n", "\n", $str), "\r", "\n");
        }
        $str = explode("\n\n", $str, 2);

        $headers = [];

        $str = preg_split('/\n(?![ \t])/', $str[0]);
        foreach ($str as $str) {
            $str = self::iconv_mime_decode($str, $mode, $charset);
            if (false === $str) {
                return false;
            }
            $str = explode(':', $str, 2);

            if (2 === \count($str)) {
                if (isset($headers[$str[0]])) {
                    if (!\is_array($headers[$str[0]])) {
                        $headers[$str[0]] = [$headers[$str[0]]];
                    }
                    $headers[$str[0]][] = ltrim($str[1]);
                } else {
                    $headers[$str[0]] = ltrim($str[1]);
                }
            }
        }

        return $headers;
    }

    public static function iconv_mime_decode($str, $mode = 0, $charset = null)
    {
        if (null === $charset) {
            $charset = self::$internalEncoding;
        }
        if (\ICONV_MIME_DECODE_CONTINUE_ON_ERROR & $mode) {
            $charset .= '//IGNORE';
        }

        if (false !== strpos($str, "\r")) {
            $str = strtr(str_replace("\r\n", "\n", $str), "\r", "\n");
        }
        $str = preg_split('/\n(?![ \t])/', rtrim($str), 2);
        $str = preg_replace('/[ \t]*\n[ \t]+/', ' ', rtrim($str[0]));
        $str = preg_split('/=\?([^?]+)\?([bqBQ])\?(.*?)\?=/', $str, -1, \PREG_SPLIT_DELIM_CAPTURE);

        $result = self::iconv('utf-8', $charset, $str[0]);
        if (false === $result) {
            return false;
        }

        $i = 1;
        $len = \count($str);

        while ($i < $len) {
            $c = strtolower($str[$i]);
            if ((\ICONV_MIME_DECODE_CONTINUE_ON_ERROR & $mode)
              && 'utf-8' !== $c
              && !isset(self::$alias[$c])
              && !self::loadMap('from.', $c, $d)) {
                $d = false;
            } elseif ('B' === strtoupper($str[$i + 1])) {
                $d = base64_decode($str[$i + 2]);
            } else {
                $d = rawurldecode(strtr(str_replace('%', '%25', $str[$i + 2]), '=_', '% '));
            }

            if (false !== $d) {
                if ('' !== $d) {
                    if ('' === $d = self::iconv($c, $charset, $d)) {
                        $str[$i + 3] = substr($str[$i + 3], 1);
                    } else {
                        $result .= $d;
                    }
                }
                $d = self::iconv('utf-8', $charset, $str[$i + 3]);
                if ('' !== trim($d)) {
                    $result .= $d;
                }
            } elseif (\ICONV_MIME_DECODE_CONTINUE_ON_ERROR & $mode) {
                $result .= "=?{$str[$i]}?{$str[$i + 1]}?{$str[$i + 2]}?={$str[$i + 3]}";
            } else {
                $result = false;
                break;
            }

            $i += 4;
        }

        return $result;
    }

    public static function iconv_get_encoding($type = 'all')
    {
        switch ($type) {
            case 'input_encoding': return self::$inputEncoding;
            case 'output_encoding': return self::$outputEncoding;
            case 'internal_encoding': return self::$internalEncoding;
        }

        return [
            'input_encoding' => self::$inputEncoding,
            'output_encoding' => self::$outputEncoding,
            'internal_encoding' => self::$internalEncoding,
        ];
    }

    public static function iconv_set_encoding($type, $charset)
    {
        switch ($type) {
            case 'input_encoding': self::$inputEncoding = $charset; break;
            case 'output_encoding': self::$outputEncoding = $charset; break;
            case 'internal_encoding': self::$internalEncoding = $charset; break;
            default: return false;
        }

        return true;
    }

    public static function iconv_mime_encode($fieldName, $fieldValue, $pref = null)
    {
        if (!\is_array($pref)) {
            $pref = [];
        }

        $pref += [
            'scheme' => 'B',
            'input-charset' => self::$internalEncoding,
            'output-charset' => self::$internalEncoding,
            'line-length' => 76,
            'line-break-chars' => "\r\n",
        ];

        if (preg_match('/[\x80-\xFF]/', $fieldName)) {
            $fieldName = '';
        }

        $scheme = strtoupper(substr($pref['scheme'], 0, 1));
        $in = strtolower($pref['input-charset']);
        $out = strtolower($pref['output-charset']);

        if ('utf-8' !== $in && false === $fieldValue = self::iconv($in, 'utf-8', $fieldValue)) {
            return false;
        }

        preg_match_all('/./us', $fieldValue, $chars);

        $chars = $chars[0] ?? [];

        $lineBreak = (int) $pref['line-length'];
        $lineStart = "=?{$pref['output-charset']}?{$scheme}?";
        $lineLength = \strlen($fieldName) + 2 + \strlen($lineStart) + 2;
        $lineOffset = \strlen($lineStart) + 3;
        $lineData = '';

        $fieldValue = [];

        $Q = 'Q' === $scheme;

        foreach ($chars as $c) {
            if ('utf-8' !== $out && false === $c = self::iconv('utf-8', $out, $c)) {
                return false;
            }

            $o = $Q
                ? $c = preg_replace_callback(
                    '/[=_\?\x00-\x1F\x80-\xFF]/',
                    [__CLASS__, 'qpByteCallback'],
                    $c
                )
                : base64_encode($lineData.$c);

            if (isset($o[$lineBreak - $lineLength])) {
                if (!$Q) {
                    $lineData = base64_encode($lineData);
                }
                $fieldValue[] = $lineStart.$lineData.'?=';
                $lineLength = $lineOffset;
                $lineData = '';
            }

            $lineData .= $c;
            $Q && $lineLength += \strlen($c);
        }

        if ('' !== $lineData) {
            if (!$Q) {
                $lineData = base64_encode($lineData);
            }
            $fieldValue[] = $lineStart.$lineData.'?=';
        }

        return $fieldName.': '.implode($pref['line-break-chars'].' ', $fieldValue);
    }

    public static function iconv_strlen($s, $encoding = null)
    {
        static $hasXml = null;
        if (null === $hasXml) {
            $hasXml = \extension_loaded('xml');
        }

        if ($hasXml) {
            return self::strlen1($s, $encoding);
        }

        return self::strlen2($s, $encoding);
    }

    public static function strlen1($s, $encoding = null)
    {
        if (null === $encoding) {
            $encoding = self::$internalEncoding;
        }
        if (0 !== stripos($encoding, 'utf-8') && false === $s = self::iconv($encoding, 'utf-8', $s)) {
            return false;
        }

        return \strlen(utf8_decode($s));
    }

    public static function strlen2($s, $encoding = null)
    {
        if (null === $encoding) {
            $encoding = self::$internalEncoding;
        }
        if (0 !== stripos($encoding, 'utf-8') && false === $s = self::iconv($encoding, 'utf-8', $s)) {
            return false;
        }

        $ulenMask = self::$ulenMask;

        $i = 0;
        $j = 0;
        $len = \strlen($s);

        while ($i < $len) {
            $u = $s[$i] & "\xF0";
            $i += $ulenMask[$u] ?? 1;
            ++$j;
        }

        return $j;
    }

    public static function iconv_strpos($haystack, $needle, $offset = 0, $encoding = null)
    {
        if (null === $encoding) {
            $encoding = self::$internalEncoding;
        }

        if (0 !== stripos($encoding, 'utf-8')) {
            if (false === $haystack = self::iconv($encoding, 'utf-8', $haystack)) {
                return false;
            }
            if (false === $needle = self::iconv($encoding, 'utf-8', $needle)) {
                return false;
            }
        }

        if ($offset = (int) $offset) {
            $haystack = self::iconv_substr($haystack, $offset, 2147483647, 'utf-8');
        }
        $pos = strpos($haystack, $needle);

        return false === $pos ? false : ($offset + ($pos ? self::iconv_strlen(substr($haystack, 0, $pos), 'utf-8') : 0));
    }

    public static function iconv_strrpos($haystack, $needle, $encoding = null)
    {
        if (null === $encoding) {
            $encoding = self::$internalEncoding;
        }

        if (0 !== stripos($encoding, 'utf-8')) {
            if (false === $haystack = self::iconv($encoding, 'utf-8', $haystack)) {
                return false;
            }
            if (false === $needle = self::iconv($encoding, 'utf-8', $needle)) {
                return false;
            }
        }

        $pos = isset($needle[0]) ? strrpos($haystack, $needle) : false;

        return false === $pos ? false : self::iconv_strlen($pos ? substr($haystack, 0, $pos) : $haystack, 'utf-8');
    }

    public static function iconv_substr($s, $start, $length = 2147483647, $encoding = null)
    {
        if (null === $encoding) {
            $encoding = self::$internalEncoding;
        }
        if (0 !== stripos($encoding, 'utf-8')) {
            $encoding = null;
        } elseif (false === $s = self::iconv($encoding, 'utf-8', $s)) {
            return false;
        }

        $s = (string) $s;
        $slen = self::iconv_strlen($s, 'utf-8');
        $start = (int) $start;

        if (0 > $start) {
            $start += $slen;
        }
        if (0 > $start) {
            if (\PHP_VERSION_ID < 80000) {
                return false;
            }

            $start = 0;
        }
        if ($start >= $slen) {
            return \PHP_VERSION_ID >= 80000 ? '' : false;
        }

        $rx = $slen - $start;

        if (0 > $length) {
            $length += $rx;
        }
        if (0 === $length) {
            return '';
        }
        if (0 > $length) {
            return \PHP_VERSION_ID >= 80000 ? '' : false;
        }

        if ($length > $rx) {
            $length = $rx;
        }

        $rx = '/^'.($start ? self::pregOffset($start) : '').'('.self::pregOffset($length).')/u';

        $s = preg_match($rx, $s, $s) ? $s[1] : '';

        if (null === $encoding) {
            return $s;
        }

        return self::iconv('utf-8', $encoding, $s);
    }

    private static function loadMap($type, $charset, &$map)
    {
        if (!isset(self::$convertMap[$type.$charset])) {
            if (false === $map = self::getData($type.$charset)) {
                if ('to.' === $type && self::loadMap('from.', $charset, $map)) {
                    $map = array_flip($map);
                } else {
                    return false;
                }
            }

            self::$convertMap[$type.$charset] = $map;
        } else {
            $map = self::$convertMap[$type.$charset];
        }

        return true;
    }

    private static function utf8ToUtf8($str, $ignore)
    {
        $ulenMask = self::$ulenMask;
        $valid = self::$isValidUtf8;

        $u = $str;
        $i = $j = 0;
        $len = \strlen($str);

        while ($i < $len) {
            if ($str[$i] < "\x80") {
                $u[$j++] = $str[$i++];
            } else {
                $ulen = $str[$i] & "\xF0";
                $ulen = $ulenMask[$ulen] ?? 1;
                $uchr = substr($str, $i, $ulen);

                if (1 === $ulen || !($valid || preg_match('/^.$/us', $uchr))) {
                    if ($ignore) {
                        ++$i;
                        continue;
                    }

                    trigger_error(self::ERROR_ILLEGAL_CHARACTER);

                    return false;
                }

                $i += $ulen;

                $u[$j++] = $uchr[0];

                isset($uchr[1]) && 0 !== ($u[$j++] = $uchr[1])
                    && isset($uchr[2]) && 0 !== ($u[$j++] = $uchr[2])
                    && isset($uchr[3]) && 0 !== ($u[$j++] = $uchr[3]);
            }
        }

        return substr($u, 0, $j);
    }

    private static function mapToUtf8(&$result, array $map, $str, $ignore)
    {
        $len = \strlen($str);
        for ($i = 0; $i < $len; ++$i) {
            if (isset($str[$i + 1], $map[$str[$i].$str[$i + 1]])) {
                $result .= $map[$str[$i].$str[++$i]];
            } elseif (isset($map[$str[$i]])) {
                $result .= $map[$str[$i]];
            } elseif (!$ignore) {
                trigger_error(self::ERROR_ILLEGAL_CHARACTER);

                return false;
            }
        }

        return true;
    }

    private static function mapFromUtf8(&$result, array $map, $str, $ignore, $translit)
    {
        $ulenMask = self::$ulenMask;
        $valid = self::$isValidUtf8;

        if ($translit && !self::$translitMap) {
            self::$translitMap = self::getData('translit');
        }

        $i = 0;
        $len = \strlen($str);

        while ($i < $len) {
            if ($str[$i] < "\x80") {
                $uchr = $str[$i++];
            } else {
                $ulen = $str[$i] & "\xF0";
                $ulen = $ulenMask[$ulen] ?? 1;
                $uchr = substr($str, $i, $ulen);

                if ($ignore && (1 === $ulen || !($valid || preg_match('/^.$/us', $uchr)))) {
                    ++$i;
                    continue;
                }

                $i += $ulen;
            }

            if (isset($map[$uchr])) {
                $result .= $map[$uchr];
            } elseif ($translit) {
                if (isset(self::$translitMap[$uchr])) {
                    $uchr = self::$translitMap[$uchr];
                } elseif ($uchr >= "\xC3\x80") {
                    $uchr = \Normalizer::normalize($uchr, \Normalizer::NFD);

                    if ($uchr[0] < "\x80") {
                        $uchr = $uchr[0];
                    } elseif ($ignore) {
                        continue;
                    } else {
                        return false;
                    }
                } elseif ($ignore) {
                    continue;
                } else {
                    return false;
                }

                $str = $uchr.substr($str, $i);
                $len = \strlen($str);
                $i = 0;
            } elseif (!$ignore) {
                return false;
            }
        }

        return true;
    }

    private static function qpByteCallback(array $m)
    {
        return '='.strtoupper(dechex(\ord($m[0])));
    }

    private static function pregOffset($offset)
    {
        $rx = [];
        $offset = (int) $offset;

        while ($offset > 65535) {
            $rx[] = '.{65535}';
            $offset -= 65535;
        }

        return implode('', $rx).'.{'.$offset.'}';
    }

    private static function getData($file)
    {
        if (file_exists($file = __DIR__.'/Resources/charset/'.$file.'.php')) {
            return require $file;
        }

        return false;
    }
}
