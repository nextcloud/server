<?php

namespace Punic;

use Exception as PHPException;

/**
 * Plural helper stuff.
 */
class Plural
{
    /**
     * Plural rule type: cardinal (eg 1, 2, 3, ...).
     *
     * @var string
     */
    const RULETYPE_CARDINAL = 'cardinal';

    /**
     * Plural rule type: ordinal (eg 1st, 2nd, 3rd, ...).
     *
     * @var string
     */
    const RULETYPE_ORDINAL = 'ordinal';

    /**
     * Return the list of applicable plural rule for a locale.
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return array<string> Returns a list containing some the following values: 'zero', 'one', 'two', 'few', 'many', 'other' ('other' will be always there)
     */
    public static function getRules($locale = '')
    {
        $node = Data::getLanguageNode(Data::getGeneric('plurals'), $locale);

        return array_merge(
            array_keys($node),
            array('other')
        );
    }

    /**
     * @deprecated Use getRuleOfType with a Plural::RULETYPE_CARDINAL type
     *
     * @param string|int|float $number
     * @param string $locale
     *
     * @throws \Punic\Exception\BadArgumentType
     * @throws \Exception
     *
     * @return string
     */
    public static function getRule($number, $locale = '')
    {
        return self::getRuleOfType($number, self::RULETYPE_CARDINAL);
    }

    /**
     * Return the plural rule ('zero', 'one', 'two', 'few', 'many' or 'other') for a number and a locale.
     *
     * @param string|int|float $number The number to check the plural rule for for
     * @param string $type The type of plural rules (one of the \Punic\Plural::RULETYPE_... constants)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception\BadArgumentType Throws a \Punic\Exception\BadArgumentType if $number is not a valid number
     * @throws \Punic\Exception\ValueNotInList Throws a \Punic\Exception\ValueNotInList if $type is not valid
     * @throws \Exception Throws a \Exception if there were problems calculating the plural rule
     *
     * @return string Returns one of the following values: 'zero', 'one', 'two', 'few', 'many', 'other'
     */
    public static function getRuleOfType($number, $type, $locale = '')
    {
        if (is_int($number)) {
            $intPartAbs = (string) abs($number);
            $floatPart = '';
        } elseif (is_float($number)) {
            $s = (string) $number;
            if (strpos($s, '.') === false) {
                $intPart = $s;
                $floatPart = '';
            } else {
                list($intPart, $floatPart) = explode('.', $s);
            }
            $intPartAbs = (string) abs((int) $intPart);
        } elseif (is_string($number) && $number !== '') {
            $m = null;
            if (preg_match('/^[+|\\-]?\\d+\\.?$/', $number)) {
                $v = (int) $number;
                $intPartAbs = (string) abs($v);
                $floatPart = '';
            } elseif (preg_match('/^(\\d*)\\.(\\d+)$/', $number, $m)) {
                list($intPart, $floatPart) = explode('.', $number);
                $v = @(int) $intPart;
                $intPartAbs = (string) abs($v);
            } else {
                throw new Exception\BadArgumentType($number, 'number');
            }
        } else {
            throw new Exception\BadArgumentType($number, 'number');
        }
        // 'n' => '%1$s', // absolute value of the source number (integer and decimals).
        $v1 = $intPartAbs . (strlen($floatPart) ? ".{$floatPart}" : '');
        // 'i' => '%2$s', // integer digits of n
        $v2 = $intPartAbs;
        // 'v' => '%3$s', // number of visible fraction digits in n, with trailing zeros.
        $v3 = strlen($floatPart);
        // 'w' => '%4$s', // number of visible fraction digits in n, without trailing zeros.
        $v4 = strlen(rtrim($floatPart, '0'));
        // 'f' => '%5$s', // visible fractional digits in n, with trailing zeros.
        $v5 = strlen($floatPart) ? (string) ((int) $floatPart) : '0';
        // 't' => '%6$s', // visible fractional digits in n, without trailing zeros.
        $v6 = trim($floatPart, '0');
        if ($v6 === '') {
            $v6 = '0';
        }
        // 'c' => compact decimal exponent value: exponent of the power of 10 used in compact decimal formatting.
        // 'e' => currently, synonym for ‘c’. however, may be redefined in the future.
        // Not yet supported
        $v7 = '0';
        $result = 'other';
        $identifierMap = array(
            self::RULETYPE_CARDINAL => 'plurals',
            self::RULETYPE_ORDINAL => 'ordinals',
        );
        if (!isset($identifierMap[$type])) {
            throw new Exception\ValueNotInList($type, array_keys($identifierMap));
        }
        $identifier = $identifierMap[$type];
        $node = Data::getLanguageNode(Data::getGeneric($identifier), $locale);
        foreach ($node as $rule => $formulaPattern) {
            $formula = sprintf($formulaPattern, $v1, $v2, $v3, $v4, $v5, $v6, $v7);
            $check = str_replace(array('static::inRange(', ' and ', ' or ', ', false, ', ', true, ', ', array('), ' , ', $formula);
            if (preg_match('/[a-z]/', $check)) {
                throw new PHPException('Bad formula!');
            }
            // fix for difference in modulo (%) in the definition and the one implemented in PHP for decimal numbers
            while (preg_match('/(\\d+\\.\\d+) % (\\d+(\\.\\d+)?)/', $formula, $m)) {
                list(, $decimalPart) = explode('.', $m[1], 2);
                $decimals = strlen(rtrim($decimalPart, '0'));
                if ($decimals > 0) {
                    $pow = (int) pow(10, $decimals);
                    $repl = '(' . (string) ((int) ((float) $m[1] * $pow)) . ' % ' . (string) ((int) ((float) ($m[2] * $pow))) . ') / ' . $pow;
                } else {
                    $repl = (string) ((int) $m[1]) . ' % ' . $m[2];
                }
                $formula = str_replace($m[0], $repl, $formula);
            }
            $formulaResult = @eval("return ({$formula}) ? 'yes' : 'no';");
            if ($formulaResult === 'yes') {
                $result = $rule;
                break;
            }
            if ($formulaResult !== 'no') {
                throw new PHPException('There was a problem in the formula ' . $formulaPattern);
            }
        }

        return $result;
    }

    /**
     * @param int|string|array $value
     * @param bool $mustBeIncluded
     *
     * @return bool
     */
    protected static function inRange($value, $mustBeIncluded)
    {
        if (is_int($value)) {
            $isInt = true;
        } elseif ($value == (int) $value) {
            $isInt = true;
        } else {
            $isInt = false;
        }
        $rangeValues = (func_num_args() > 2) ? array_slice(func_get_args(), 2) : array();
        $included = false;
        foreach ($rangeValues as $rangeValue) {
            if (is_array($rangeValue)) {
                if ($isInt && ($value >= $rangeValue[0]) && ($value <= $rangeValue[1])) {
                    $included = true;
                    break;
                }
            } elseif ($value == $rangeValue) {
                $included = true;
                break;
            }
        }

        return $included == $mustBeIncluded;
    }
}
