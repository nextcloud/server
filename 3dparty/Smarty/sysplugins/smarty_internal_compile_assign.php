<?php

/**
 * Smarty Internal Plugin Compile Assign
 *
 * Compiles the {assign} tag
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Assign Class
 */
class Smarty_Internal_Compile_Assign extends Smarty_Internal_CompileBase {
    /**
     * Compiles code for the {assign} tag
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @param array $parameter array with compilation parameter
     * @return string compiled code
     */
    public function compile($args, $compiler, $parameter)
    {
        $this->compiler = $compiler;
        // the following must be assigned at runtime because it will be overwritten in Smarty_Internal_Compile_Append
        $this->required_attributes = array('var', 'value');
        $this->shorttag_order = array('var', 'value');
        $this->optional_attributes = array('scope');
        $_nocache = 'null';
        $_scope = 'null';
        // check and get attributes
        $_attr = $this->_get_attributes($args);
		// nocache ?
        if ($this->compiler->tag_nocache || $this->compiler->nocache) {
            $_nocache = 'true';
            // create nocache var to make it know for further compiling
            $compiler->template->tpl_vars[trim($_attr['var'], "'")] = new Smarty_variable(null, true);
        }
        // scope setup
        if (isset($_attr['scope'])) {
            $_attr['scope'] = trim($_attr['scope'], "'\"");
            if ($_attr['scope'] == 'parent') {
                $_scope = Smarty::SCOPE_PARENT;
            } elseif ($_attr['scope'] == 'root') {
                $_scope = Smarty::SCOPE_ROOT;
            } elseif ($_attr['scope'] == 'global') {
                $_scope = Smarty::SCOPE_GLOBAL;
            } else {
                $this->compiler->trigger_template_error('illegal value for "scope" attribute', $this->compiler->lex->taglineno);
            }
        }
        // compiled output
        if (isset($parameter['smarty_internal_index'])) {
            return "<?php if (!isset(\$_smarty_tpl->tpl_vars[$_attr[var]]) || !is_array(\$_smarty_tpl->tpl_vars[$_attr[var]]->value)) \$_smarty_tpl->createLocalArrayVariable($_attr[var], $_nocache, $_scope);\n\$_smarty_tpl->tpl_vars[$_attr[var]]->value$parameter[smarty_internal_index] = $_attr[value];?>";
        } else {
            return "<?php \$_smarty_tpl->tpl_vars[$_attr[var]] = new Smarty_variable($_attr[value], $_nocache, $_scope);?>";
        }
    }
}

?>