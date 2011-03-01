<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifierCompiler
 */

/**
 * Smarty count_characters modifier plugin
 *
 * Type:     modifier<br>
 * Name:     count_characteres<br>
 * Purpose:  count the number of characters in a text
 *
 * @link http://smarty.php.net/manual/en/language.modifier.count.characters.php count_characters (Smarty online manual)
 * @author Uwe Tews
 * @param array $params parameters
 * @return string with compiled code
 */
function smarty_modifiercompiler_count_characters($params, $compiler)
{
    // mb_ functions available?
    if (function_exists('mb_strlen')) {
        // count also spaces?
        if (isset($params[1]) && $params[1] == 'true') {
            return '((mb_detect_encoding(' . $params[0] . ', \'UTF-8, ISO-8859-1\') === \'UTF-8\') ? mb_strlen(' . $params[0] . ', SMARTY_RESOURCE_CHAR_SET) : strlen(' . $params[0] . '))';
        }
        return '((mb_detect_encoding(' . $params[0] . ', \'UTF-8, ISO-8859-1\') === \'UTF-8\') ? preg_match_all(\'#[^\s\pZ]#u\', ' . $params[0] . ', $tmp) : preg_match_all(\'/[^\s]/\',' . $params[0] . ', $tmp))';
    } else {
        // count also spaces?
        if (isset($params[1]) && $params[1] == 'true') {
            return 'strlen(' . $params[0] . ')';
        }
        return 'preg_match_all(\'/[^\s]/\',' . $params[0] . ', $tmp)';
    }
}

?>