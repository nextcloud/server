<?php

/**
 * Smarty Internal Plugin Compile Ldelim
 *
 * Compiles the {ldelim} tag
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Ldelim Class
 */
class Smarty_Internal_Compile_Ldelim extends Smarty_Internal_CompileBase {
    /**
     * Compiles code for the {ldelim} tag
     *
     * This tag does output the left delimiter
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @return string compiled code
     */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $_attr = $this->_get_attributes($args);
        if ($_attr['nocache'] === true) {
        	$this->compiler->trigger_template_error('nocache option not allowed', $this->compiler->lex->taglineno);
        }
        // this tag does not return compiled code
        $this->compiler->has_code = true;
        return $this->compiler->smarty->left_delimiter;
    }
}

?>
