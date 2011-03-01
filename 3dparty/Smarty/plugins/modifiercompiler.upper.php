<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifierCompiler
 */

/**
 * Smarty upper modifier plugin
 *
 * Type:     modifier<br>
 * Name:     lower<br>
 * Purpose:  convert string to uppercase
 *
 * @link http://smarty.php.net/manual/en/language.modifier.upper.php lower (Smarty online manual)
 * @author Uwe Tews
 * @param array $params parameters
 * @return string with compiled code
 */
function smarty_modifiercompiler_upper($params, $compiler)
{
    if (function_exists('mb_strtoupper')) {
        return '((mb_detect_encoding(' . $params[0] . ', \'UTF-8, ISO-8859-1\') === \'UTF-8\') ? mb_strtoupper(' . $params[0] . ',SMARTY_RESOURCE_CHAR_SET) : strtoupper(' . $params[0] . '))' ;
    } else {
        return 'strtoupper(' . $params[0] . ')';
    }
}

?>