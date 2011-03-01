<?php
/**
 * Smarty plugin
 *
 * This plugin is only for Smarty2 BC
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {math} function plugin
 *
 * Type:     function<br>
 * Name:     math<br>
 * Purpose:  handle math computations in template<br>
 * @link http://smarty.php.net/manual/en/language.function.math.php {math}
 *          (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param array $params parameters
 * @param object $template template object
 * @return string|null
 */
function smarty_function_math($params, $template)
{
    // be sure equation parameter is present
    if (empty($params['equation'])) {
        trigger_error("math: missing equation parameter",E_USER_WARNING);
        return;
    }

    $equation = $params['equation'];

    // make sure parenthesis are balanced
    if (substr_count($equation,"(") != substr_count($equation,")")) {
        trigger_error("math: unbalanced parenthesis",E_USER_WARNING);
        return;
    }

    // match all vars in equation, make sure all are passed
    preg_match_all("!(?:0x[a-fA-F0-9]+)|([a-zA-Z][a-zA-Z0-9_]*)!",$equation, $match);
    $allowed_funcs = array('int','abs','ceil','cos','exp','floor','log','log10',
                           'max','min','pi','pow','rand','round','sin','sqrt','srand','tan');

    foreach($match[1] as $curr_var) {
        if ($curr_var && !in_array($curr_var, array_keys($params)) && !in_array($curr_var, $allowed_funcs)) {
            trigger_error("math: function call $curr_var not allowed",E_USER_WARNING);
            return;
        }
    }

    foreach($params as $key => $val) {
        if ($key != "equation" && $key != "format" && $key != "assign") {
            // make sure value is not empty
            if (strlen($val)==0) {
                trigger_error("math: parameter $key is empty",E_USER_WARNING);
                return;
            }
            if (!is_numeric($val)) {
                trigger_error("math: parameter $key: is not numeric",E_USER_WARNING);
                return;
            }
            $equation = preg_replace("/\b$key\b/", " \$params['$key'] ", $equation);
        }
    }
    $smarty_math_result = null;
    eval("\$smarty_math_result = ".$equation.";");

    if (empty($params['format'])) {
        if (empty($params['assign'])) {
            return $smarty_math_result;
        } else {
            $template->assign($params['assign'],$smarty_math_result);
        }
    } else {
        if (empty($params['assign'])){
            printf($params['format'],$smarty_math_result);
        } else {
            $template->assign($params['assign'],sprintf($params['format'],$smarty_math_result));
        }
    }
}

?>