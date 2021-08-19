<?php

namespace GuzzleHttp\Psr7;

final class Query
{
    /**
     * Parse a query string into an associative array.
     *
     * If multiple values are found for the same key, the value of that key
     * value pair will become an array. This function does not parse nested
     * PHP style arrays into an associative array (e.g., `foo[a]=1&foo[b]=2`
     * will be parsed into `['foo[a]' => '1', 'foo[b]' => '2'])`.
     *
     * @param string   $str         Query string to parse
     * @param int|bool $urlEncoding How the query string is encoded
     *
     * @return array
     */
    public static function parse($str, $urlEncoding = true)
    {
        $result = [];

        if ($str === '') {
            return $result;
        }

        if ($urlEncoding === true) {
            $decoder = function ($value) {
                return rawurldecode(str_replace('+', ' ', $value));
            };
        } elseif ($urlEncoding === PHP_QUERY_RFC3986) {
            $decoder = 'rawurldecode';
        } elseif ($urlEncoding === PHP_QUERY_RFC1738) {
            $decoder = 'urldecode';
        } else {
            $decoder = function ($str) {
                return $str;
            };
        }

        foreach (explode('&', $str) as $kvp) {
            $parts = explode('=', $kvp, 2);
            $key = $decoder($parts[0]);
            $value = isset($parts[1]) ? $decoder($parts[1]) : null;
            if (!isset($result[$key])) {
                $result[$key] = $value;
            } else {
                if (!is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $value;
            }
        }

        return $result;
    }

    /**
     * Build a query string from an array of key value pairs.
     *
     * This function can use the return value of `parse()` to build a query
     * string. This function does not modify the provided keys when an array is
     * encountered (like `http_build_query()` would).
     *
     * @param array     $params   Query string parameters.
     * @param int|false $encoding Set to false to not encode, PHP_QUERY_RFC3986
     *                            to encode using RFC3986, or PHP_QUERY_RFC1738
     *                            to encode using RFC1738.
     *
     * @return string
     */
    public static function build(array $params, $encoding = PHP_QUERY_RFC3986)
    {
        if (!$params) {
            return '';
        }

        if ($encoding === false) {
            $encoder = function ($str) {
                return $str;
            };
        } elseif ($encoding === PHP_QUERY_RFC3986) {
            $encoder = 'rawurlencode';
        } elseif ($encoding === PHP_QUERY_RFC1738) {
            $encoder = 'urlencode';
        } else {
            throw new \InvalidArgumentException('Invalid type');
        }

        $qs = '';
        foreach ($params as $k => $v) {
            $k = $encoder($k);
            if (!is_array($v)) {
                $qs .= $k;
                if ($v !== null) {
                    $qs .= '=' . $encoder($v);
                }
                $qs .= '&';
            } else {
                foreach ($v as $vv) {
                    $qs .= $k;
                    if ($vv !== null) {
                        $qs .= '=' . $encoder($vv);
                    }
                    $qs .= '&';
                }
            }
        }

        return $qs ? (string) substr($qs, 0, -1) : '';
    }
}
