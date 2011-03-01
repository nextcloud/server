<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifierCompiler
 */

/**
 * Smarty strip modifier plugin
 *
 * Type:     modifier<br>
 * Name:     strip<br>
 * Purpose:  Replace all repeated spaces, newlines, tabs
 *              with a single space or supplied replacement string.<br>
 * Example:  {$var|strip} {$var|strip:"&nbsp;"}
 * Date:     September 25th, 2002
 *
 * @link http://smarty.php.net/manual/en/language.modifier.strip.php strip (Smarty online manual)
 * @author Uwe Tews
 * @param array $params parameters
 * @return string with compiled code
 */

function smarty_modifiercompiler_strip($params, $compiler)
{
    if (!isset($params[1])) {
        $params[1] = "' '";
    }
    return "preg_replace('!\s+!', {$params[1]},{$params[0]})";
}

?>