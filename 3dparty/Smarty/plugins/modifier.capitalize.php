<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty capitalize modifier plugin
 *
 * Type:     modifier<br>
 * Name:     capitalize<br>
 * Purpose:  capitalize words in the string
 *
 * @link
 * @author Monte Ohrt <monte at ohrt dot com>
 * @param string $
 * @return string
 */
function smarty_modifier_capitalize($string, $uc_digits = false)
{
    // uppercase with php function ucwords
    $upper_string = ucwords($string);
    // check for any missed hyphenated words
    $upper_string = preg_replace("!(^|[^\p{L}'])([\p{Ll}])!ue", "'\\1'.ucfirst('\\2')", $upper_string);
    // check uc_digits case
    if (!$uc_digits) {
        if (preg_match_all("!\b([\p{L}]*[\p{N}]+[\p{L}]*)\b!u", $string, $matches, PREG_OFFSET_CAPTURE)) {
            foreach($matches[1] as $match)
            $upper_string = substr_replace($upper_string, $match[0], $match[1], strlen($match[0]));
        }
    }
    return $upper_string;
}

?>