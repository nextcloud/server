<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifierCompiler
 */

/**
 * Smarty lower modifier plugin
 *
 * Type:     modifier<br>
 * Name:     lower<br>
 * Purpose:  convert string to lowercase
 *
 * @link http://smarty.php.net/manual/en/language.modifier.lower.php lower (Smarty online manual)
 * @author Monte Ohrt <monte at ohrt dot com>
 * @author Uwe Tews
 * @param array $params parameters
 * @return string with compiled code
 */

function smarty_modifiercompiler_lower($params, $compiler)
{
    if (function_exists('mb_strtolower')) {
        return '((mb_detect_encoding(' . $params[0] . ', \'UTF-8, ISO-8859-1\') === \'UTF-8\') ? mb_strtolower(' . $params[0] . ',SMARTY_RESOURCE_CHAR_SET) : strtolower(' . $params[0] . '))' ;
    } else {
        return 'strtolower(' . $params[0] . ')';
    }
}

?>