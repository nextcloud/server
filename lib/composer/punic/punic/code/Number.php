<?php

namespace Punic;

/**
 * Numbers helpers.
 */
class Number
{
    /**
     * Check if a variable contains a valid number for the specified locale.
     *
     * @param string $value The string value to check
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return bool
     */
    public static function isNumeric($value, $locale = '')
    {
        return static::unformat($value, $locale) !== null;
    }

    /**
     * Check if a variable contains a valid integer number for the specified locale.
     *
     * @param string $value The string value to check
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return bool
     */
    public static function isInteger($value, $locale = '')
    {
        $result = false;
        $number = static::unformat($value, $locale);
        if (is_int($number)) {
            $result = true;
        } elseif (is_float($number)) {
            if ($number === floatval(round($number))) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Localize a number representation (for instance, converts 1234.5 to '1,234.5' in case of English and to '1.234,5' in case of Italian).
     *
     * @param numeric $value The string value to convert
     * @param int|null $precision The wanted precision (well use {@link http://php.net/manual/function.round.php})
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string $value is not a number, otherwise returns the localized representation of the number
     */
    public static function format($value, $precision = null, $locale = '')
    {
        $result = '';
        $number = null;
        if (is_int($value) || is_float($value)) {
            $number = $value;
        } elseif (is_string($value) && isset($value[0])) {
            if (preg_match('/^[\\-+]?\\d+$/', $value)) {
                $number = intval($value);
            } elseif (preg_match('/^[\\-+]?(\\d*)\\.(\\d*)$/', $value, $m)) {
                if (!isset($m[1])) {
                    $m[1] = '';
                }
                if (!isset($m[2])) {
                    $m[2] = '';
                }
                if ((isset($m[1][0])) || (isset($m[2][0]))) {
                    $number = floatval($value);
                    if (!is_numeric($precision)) {
                        $precision = strlen($m[2]);
                    }
                }
            }
        }
        if ($number !== null) {
            $precision = is_numeric($precision) ? intval($precision) : null;
            if ($precision !== null) {
                $value = round($value, $precision);
            }
            $data = Data::get('numbers', $locale);
            $decimal = $data['symbols']['decimal'];
            $groupLength = (isset($data['groupLength']) && is_numeric($data['groupLength'])) ? intval($data['groupLength']) : 3;
            if ($value < 0) {
                $sign = $data['symbols']['minusSign'];
                $value = abs($value);
            } else {
                $sign = '';
            }
            $full = explode('.', strval($value), 2);
            $intPart = $full[0];
            $floatPath = (count($full) > 1) ? $full[1] : '';
            $len = strlen($intPart);
            if (($groupLength > 0) && ($len > $groupLength)) {
                $groupSign = $data['symbols']['group'];
                $preLength = 1 + (($len - 1) % 3);
                $pre = substr($intPart, 0, $preLength);
                $intPart = $pre.$groupSign.implode($groupSign, str_split(substr($intPart, $preLength), $groupLength));
            }
            $result = $sign.$intPart;
            if ($precision === null) {
                if (isset($floatPath[0])) {
                    $result .= $decimal.$floatPath;
                }
            } elseif ($precision > 0) {
                $result .= $decimal.substr(str_pad($floatPath, $precision, '0', STR_PAD_RIGHT), 0, $precision);
            }
        }

        return $result;
    }

    /**
     * Convert a localized representation of a number to a number (for instance, converts the string '1,234' to 1234 in case of English and to 1.234 in case of Italian).
     *
     * @param string $value The string value to convert
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return int|float|null Returns null if $value is not valid, the numeric value otherwise
     */
    public static function unformat($value, $locale = '')
    {
        $result = null;
        if (is_int($value) || is_float($value)) {
            $result = $value;
        } elseif (is_string($value) && isset($value[0])) {
            $data = Data::get('numbers', $locale);
            $plus = $data['symbols']['plusSign'];
            $plusQ = preg_quote($plus);
            $minus = $data['symbols']['minusSign'];
            $minusQ = preg_quote($minus);
            $decimal = $data['symbols']['decimal'];
            $decimalQ = preg_quote($decimal);
            $group = $data['symbols']['group'];
            $groupQ = preg_quote($group);
            $ok = true;
            if (preg_match('/^'."($plusQ|$minusQ)?(\\d+(?:$groupQ\\d+)*)".'$/', $value, $m)) {
                $sign = $m[1];
                $int = $m[2];
                $float = null;
            } elseif (preg_match('/^'."($plusQ|$minusQ)?(\\d+(?:$groupQ\\d+)*)$decimalQ".'$/', $value, $m)) {
                $sign = $m[1];
                $int = $m[2];
                $float = '';
            } elseif (preg_match('/^'."($plusQ|$minusQ)?(\\d+(?:$groupQ\\d+)*)$decimalQ(\\d+)".'$/', $value, $m)) {
                $sign = $m[1];
                $int = $m[2];
                $float = $m[3];
            } elseif (preg_match('/^'."($plusQ|$minusQ)?$decimalQ(\\d+)".'$/', $value, $m)) {
                $sign = $m[1];
                $int = '0';
                $float = $m[2];
            } else {
                $ok = false;
                $float = $int = $sign = null;
            }
            if ($ok) {
                if ($sign === $minus) {
                    $sign = '-';
                } else {
                    $sign = '';
                }
                $int = str_replace($group, '', $int);
                if ($float === null) {
                    $result = intval("$sign$int");
                } else {
                    $result = floatval("$sign$int.$float");
                }
            }
        }

        return $result;
    }
}
