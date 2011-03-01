<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifierCompiler
 */

/**
 * Smarty default modifier plugin
 *
 * Type:     modifier<br>
 * Name:     default<br>
 * Purpose:  designate default value for empty variables
 *
 * @link http://smarty.php.net/manual/en/language.modifier.default.php default (Smarty online manual)
 * @author Uwe Tews
 * @param array $params parameters
 * @return string with compiled code
 */
function smarty_modifiercompiler_default ($params, $compiler)
{
    $output = $params[0];
    if (!isset($params[1])) {
        $params[1] = "''";
    }
    for ($i = 1, $cnt = count($params); $i < $cnt; $i++) {
        $output = '(($tmp = @' . $output . ')===null||$tmp===\'\' ? ' . $params[$i] . ' : $tmp)';
    }
    return $output;
}

?>