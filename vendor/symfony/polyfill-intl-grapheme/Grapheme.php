<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Grapheme;

\define('SYMFONY_GRAPHEME_CLUSTER_RX', ((float) \PCRE_VERSION < 10 ? (float) \PCRE_VERSION >= 8.32 : (float) \PCRE_VERSION >= 10.39) ? '\X' : Grapheme::GRAPHEME_CLUSTER_RX);

/**
 * Partial intl implementation in pure PHP.
 *
 * Implemented:
 * - grapheme_extract  - Extract a sequence of grapheme clusters from a text buffer, which must be encoded in UTF-8
 * - grapheme_stripos  - Find position (in grapheme units) of first occurrence of a case-insensitive string
 * - grapheme_stristr  - Returns part of haystack string from the first occurrence of case-insensitive needle to the end of haystack
 * - grapheme_strlen   - Get string length in grapheme units
 * - grapheme_strpos   - Find position (in grapheme units) of first occurrence of a string
 * - grapheme_strripos - Find position (in grapheme units) of last occurrence of a case-insensitive string
 * - grapheme_strrpos  - Find position (in grapheme units) of last occurrence of a string
 * - grapheme_strstr   - Returns part of haystack string from the first occurrence of needle to the end of haystack
 * - grapheme_substr   - Return part of a string
 *
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
final class Grapheme
{
    // (CRLF|([ZWNJ-ZWJ]|T+|L*(LV?V+|LV|LVT)T*|L+|[^Control])[Extend]*|[Control])
    // This regular expression is a work around for http://bugs.exim.org/1279
    public const GRAPHEME_CLUSTER_RX = '(?:\r\n|(?:[ -~\x{200C}\x{200D}]|[ᆨ-ᇹ]+|[ᄀ-ᅟ]*(?:[가개갸걔거게겨계고과괘괴교구궈궤귀규그긔기까깨꺄꺠꺼께껴꼐꼬꽈꽤꾀꾜꾸꿔꿰뀌뀨끄끠끼나내냐냬너네녀녜노놔놰뇌뇨누눠눼뉘뉴느늬니다대댜댸더데뎌뎨도돠돼되됴두둬뒈뒤듀드듸디따때땨떄떠떼뗘뗴또똬뙈뙤뚀뚜뚸뛔뛰뜌뜨띄띠라래랴럐러레려례로롸뢔뢰료루뤄뤠뤼류르릐리마매먀먜머메며몌모뫄뫠뫼묘무뭐뭬뮈뮤므믜미바배뱌뱨버베벼볘보봐봬뵈뵤부붜붸뷔뷰브븨비빠빼뺘뺴뻐뻬뼈뼤뽀뽜뽸뾔뾰뿌뿨쀄쀠쀼쁘쁴삐사새샤섀서세셔셰소솨쇄쇠쇼수숴쉐쉬슈스싀시싸쌔쌰썌써쎄쎠쎼쏘쏴쐐쐬쑈쑤쒀쒜쒸쓔쓰씌씨아애야얘어에여예오와왜외요우워웨위유으의이자재쟈쟤저제져졔조좌좨죄죠주줘줴쥐쥬즈즤지짜째쨔쨰쩌쩨쪄쪠쪼쫘쫴쬐쬬쭈쭤쮀쮜쮸쯔쯰찌차채챠챼처체쳐쳬초촤쵀최쵸추춰췌취츄츠츼치카캐캬컈커케켜켸코콰쾌쾨쿄쿠쿼퀘퀴큐크킈키타태탸턔터테텨톄토톼퇘퇴툐투퉈퉤튀튜트틔티파패퍄퍠퍼페펴폐포퐈퐤푀표푸풔풰퓌퓨프픠피하해햐햬허헤혀혜호화홰회효후훠훼휘휴흐희히]?[ᅠ-ᆢ]+|[가-힣])[ᆨ-ᇹ]*|[ᄀ-ᅟ]+|[^\p{Cc}\p{Cf}\p{Zl}\p{Zp}])[\p{Mn}\p{Me}\x{09BE}\x{09D7}\x{0B3E}\x{0B57}\x{0BBE}\x{0BD7}\x{0CC2}\x{0CD5}\x{0CD6}\x{0D3E}\x{0D57}\x{0DCF}\x{0DDF}\x{200C}\x{200D}\x{1D165}\x{1D16E}-\x{1D172}]*|[\p{Cc}\p{Cf}\p{Zl}\p{Zp}])';

    private const CASE_FOLD = [
        ['µ', 'ſ', "\xCD\x85", 'ς', "\xCF\x90", "\xCF\x91", "\xCF\x95", "\xCF\x96", "\xCF\xB0", "\xCF\xB1", "\xCF\xB5", "\xE1\xBA\x9B", "\xE1\xBE\xBE"],
        ['μ', 's', 'ι',        'σ', 'β',        'θ',        'φ',        'π',        'κ',        'ρ',        'ε',        "\xE1\xB9\xA1", 'ι'],
    ];

    public static function grapheme_extract($s, $size, $type = \GRAPHEME_EXTR_COUNT, $start = 0, &$next = 0)
    {
        if (0 > $start) {
            $start = \strlen($s) + $start;
        }

        if (!\is_scalar($s)) {
            $hasError = false;
            set_error_handler(function () use (&$hasError) { $hasError = true; });
            $next = substr($s, $start);
            restore_error_handler();
            if ($hasError) {
                substr($s, $start);
                $s = '';
            } else {
                $s = $next;
            }
        } else {
            $s = substr($s, $start);
        }
        $size = (int) $size;
        $type = (int) $type;
        $start = (int) $start;

        if (\GRAPHEME_EXTR_COUNT !== $type && \GRAPHEME_EXTR_MAXBYTES !== $type && \GRAPHEME_EXTR_MAXCHARS !== $type) {
            if (80000 > \PHP_VERSION_ID) {
                return false;
            }

            throw new \ValueError('grapheme_extract(): Argument #3 ($type) must be one of GRAPHEME_EXTR_COUNT, GRAPHEME_EXTR_MAXBYTES, or GRAPHEME_EXTR_MAXCHARS');
        }

        if (!isset($s[0]) || 0 > $size || 0 > $start) {
            return false;
        }
        if (0 === $size) {
            return '';
        }

        $next = $start;

        $s = preg_split('/('.SYMFONY_GRAPHEME_CLUSTER_RX.')/u', "\r\n".$s, $size + 1, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE);

        if (!isset($s[1])) {
            return false;
        }

        $i = 1;
        $ret = '';

        do {
            if (\GRAPHEME_EXTR_COUNT === $type) {
                --$size;
            } elseif (\GRAPHEME_EXTR_MAXBYTES === $type) {
                $size -= \strlen($s[$i]);
            } else {
                $size -= iconv_strlen($s[$i], 'UTF-8//IGNORE');
            }

            if ($size >= 0) {
                $ret .= $s[$i];
            }
        } while (isset($s[++$i]) && $size > 0);

        $next += \strlen($ret);

        return $ret;
    }

    public static function grapheme_strlen($s)
    {
        preg_replace('/'.SYMFONY_GRAPHEME_CLUSTER_RX.'/u', '', $s, -1, $len);

        return 0 === $len && '' !== $s ? null : $len;
    }

    public static function grapheme_substr($s, $start, $len = null)
    {
        if (null === $len) {
            $len = 2147483647;
        }

        preg_match_all('/'.SYMFONY_GRAPHEME_CLUSTER_RX.'/u', $s, $s);

        $slen = \count($s[0]);
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

        $rem = $slen - $start;

        if (0 > $len) {
            $len += $rem;
        }
        if (0 === $len) {
            return '';
        }
        if (0 > $len) {
            return \PHP_VERSION_ID >= 80000 ? '' : false;
        }
        if ($len > $rem) {
            $len = $rem;
        }

        return implode('', \array_slice($s[0], $start, $len));
    }

    public static function grapheme_strpos($s, $needle, $offset = 0)
    {
        return self::grapheme_position($s, $needle, $offset, 0);
    }

    public static function grapheme_stripos($s, $needle, $offset = 0)
    {
        return self::grapheme_position($s, $needle, $offset, 1);
    }

    public static function grapheme_strrpos($s, $needle, $offset = 0)
    {
        return self::grapheme_position($s, $needle, $offset, 2);
    }

    public static function grapheme_strripos($s, $needle, $offset = 0)
    {
        return self::grapheme_position($s, $needle, $offset, 3);
    }

    public static function grapheme_stristr($s, $needle, $beforeNeedle = false)
    {
        return mb_stristr($s, $needle, $beforeNeedle, 'UTF-8');
    }

    public static function grapheme_strstr($s, $needle, $beforeNeedle = false)
    {
        return mb_strstr($s, $needle, $beforeNeedle, 'UTF-8');
    }

    private static function grapheme_position($s, $needle, $offset, $mode)
    {
        $needle = (string) $needle;
        if (80000 > \PHP_VERSION_ID && !preg_match('/./us', $needle)) {
            return false;
        }
        $s = (string) $s;
        if (!preg_match('/./us', $s)) {
            return false;
        }
        if ($offset > 0) {
            $s = self::grapheme_substr($s, $offset);
        } elseif ($offset < 0) {
            if (2 > $mode) {
                $offset += self::grapheme_strlen($s);
                $s = self::grapheme_substr($s, $offset);
                if (0 > $offset) {
                    $offset = 0;
                }
            } elseif (0 > $offset += self::grapheme_strlen($needle)) {
                $s = self::grapheme_substr($s, 0, $offset);
                $offset = 0;
            } else {
                $offset = 0;
            }
        }

        // As UTF-8 is self-synchronizing, and we have ensured the strings are valid UTF-8,
        // we can use normal binary string functions here. For case-insensitive searches,
        // case fold the strings first.
        $caseInsensitive = $mode & 1;
        $reverse = $mode & 2;
        if ($caseInsensitive) {
            // Use the same case folding mode as mbstring does for mb_stripos().
            // Stick to SIMPLE case folding to avoid changing the length of the string, which
            // might result in offsets being shifted.
            $mode = \defined('MB_CASE_FOLD_SIMPLE') ? \MB_CASE_FOLD_SIMPLE : \MB_CASE_LOWER;
            $s = mb_convert_case($s, $mode, 'UTF-8');
            $needle = mb_convert_case($needle, $mode, 'UTF-8');

            if (!\defined('MB_CASE_FOLD_SIMPLE')) {
                $s = str_replace(self::CASE_FOLD[0], self::CASE_FOLD[1], $s);
                $needle = str_replace(self::CASE_FOLD[0], self::CASE_FOLD[1], $needle);
            }
        }
        if ($reverse) {
            $needlePos = strrpos($s, $needle);
        } else {
            $needlePos = strpos($s, $needle);
        }

        return false !== $needlePos ? self::grapheme_strlen(substr($s, 0, $needlePos)) + $offset : false;
    }
}
