<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty spacify modifier plugin
 *
 * Type:     modifier<br>
 * Name:     spacify<br>
 * Purpose:  add spaces between characters in a string
 *
 * @link http://smarty.php.net/manual/en/language.modifier.spacify.php spacify (Smarty online manual)
 * @author Monte Ohrt <monte at ohrt dot com>
 * @param string $
 * @param string $
 * @return string
 */
function smarty_modifier_spacify($string, $spacify_char = ' ')
{
    // mb_ functions available?
    if (function_exists('mb_strlen') && mb_detect_encoding($string, 'UTF-8, ISO-8859-1') === 'UTF-8') {
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, "UTF-8");
            $string = mb_substr($string, 1, $strlen, "UTF-8");
            $strlen = mb_strlen($string);
        }
        return implode($spacify_char, $array);
    } else {
        return implode($spacify_char, preg_split('//', $string, -1));
    }
}

?>