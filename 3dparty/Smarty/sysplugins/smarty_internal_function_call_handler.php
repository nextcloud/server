<?php
/**
 * Smarty Internal Plugin Function Call Handler
 *
 * @package Smarty
 * @subpackage PluginsInternal
 * @author Uwe Tews
 */

/**
 * This class does call function defined with the {function} tag
 */
class Smarty_Internal_Function_Call_Handler extends Smarty_Internal_Template {
    static function call ($_name, $_template, $_params, $_hash, $_nocache)
    {
        if ($_nocache) {
            $_function = "smarty_template_function_{$_name}_nocache";
            $_template->smarty->template_functions[$_name]['called_nocache'] = true;
        } else {
            $_function = "smarty_template_function_{$_hash}_{$_name}";
        }
        if (!is_callable($_function)) {
            $_code = "function {$_function}(\$_smarty_tpl,\$params) {
    \$saved_tpl_vars = \$_smarty_tpl->tpl_vars;
    foreach (\$_smarty_tpl->template_functions['{$_name}']['parameter'] as \$key => \$value) {\$_smarty_tpl->tpl_vars[\$key] = new Smarty_variable(trim(\$value,'\''));};
    foreach (\$params as \$key => \$value) {\$_smarty_tpl->tpl_vars[\$key] = new Smarty_variable(\$value);}?>";
            if ($_nocache) {
                $_code .= preg_replace(array("!<\?php echo \\'/\*%%SmartyNocache:{$_template->smarty->template_functions[$_name]['nocache_hash']}%%\*/|/\*/%%SmartyNocache:{$_template->smarty->template_functions[$_name]['nocache_hash']}%%\*/\\';\?>!",
                        "!\\\'!"), array('', "'"), $_template->smarty->template_functions[$_name]['compiled']);
            } else {
                $_code .= preg_replace("/{$_template->smarty->template_functions[$_name]['nocache_hash']}/", $_template->properties['nocache_hash'], $_template->smarty->template_functions[$_name]['compiled']);
            }
            $_code .= "<?php \$_smarty_tpl->tpl_vars = \$saved_tpl_vars;}";
            eval($_code);
        }
        $_function($_template, $_params);
    }
}

?>