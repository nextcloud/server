<?php


namespace Office365\PHP\Client\Runtime\OData;


class ODataPathParser
{

    /**
     * @param $string
     * @return array
     */
    public static function parsePathString($string)
    {
        $level = 0;       // number of nested sets of brackets
        $ret = array(''); // array to return
        $cur = 0;         // current index in the array to return, for convenience

        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            switch ($string[$i]) {
                case '(':
                    $level++;
                    $ret[$cur] .= '(';
                    break;
                case ')':
                    $level--;
                    $ret[$cur] .= ')';
                    break;
                case '/':
                    if ($level == 0) {
                        $cur++;
                        $ret[$cur] = '';
                        break;
                    }
                // else fallthrough
                default:
                    $ret[$cur] .= $string[$i];
                    break;
            }
        }
        return $ret;
    }

    /**
     * @param $methodName
     * @param array $methodParameters
     * @return string
     */
    public static function fromMethod($methodName, array $methodParameters = null)
    {
        $url = isset($methodName) ? $methodName : "";
        if (!isset($methodParameters))
            return $url;

        if (count(array_filter(array_keys($methodParameters), 'is_string')) === 0) {
            $url = $url . "(" . implode(',', array_map(
                        function ($value) {
                            $encValue = self::escapeValue($value);
                            return "$encValue";
                        }, $methodParameters)
                ) . ")";
        } else {
            $url = $url . "(" . implode(',', array_map(
                        function ($key, $value) {
                            $encValue = self::escapeValue($value);
                            return "$key=$encValue";
                        }, array_keys($methodParameters), $methodParameters)
                ) . ")";
        }
        return $url;
    }


    private static function escapeValue($value)
    {
        if (is_string($value))
            $value = "'" . $value . "'";
        elseif (is_bool($value))
            $value = var_export($value, true);
        return $value;
    }

}
