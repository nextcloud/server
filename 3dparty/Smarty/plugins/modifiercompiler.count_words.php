<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifierCompiler
 */

/**
 * Smarty count_words modifier plugin
 *
 * Type:     modifier<br>
 * Name:     count_words<br>
 * Purpose:  count the number of words in a text
 *
 * @link http://smarty.php.net/manual/en/language.modifier.count.words.php count_words (Smarty online manual)
 * @author Uwe Tews
 * @param array $params parameters
 * @return string with compiled code
*/
function smarty_modifiercompiler_count_words($params, $compiler)
{
    // mb_ functions available?
    if (function_exists('mb_strlen')) {
        return '((mb_detect_encoding(' . $params[0] . ', \'UTF-8, ISO-8859-1\') === \'UTF-8\') ? preg_match_all(\'#[\w\pL]+#u\', ' . $params[0] . ', $tmp) : preg_match_all(\'#\w+#\',' . $params[0] . ', $tmp))';
    } else {
        return 'str_word_count(' . $params[0] . ')';
    }
}

?>