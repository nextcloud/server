<?php

/**
 * Smarty Internal Plugin Compile Eval
 *
 * Compiles the {eval} tag
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Eval Class
 */
class Smarty_Internal_Compile_Eval extends Smarty_Internal_CompileBase {
    public $required_attributes = array('var');
    public $optional_attributes = array('assign');
    public $shorttag_order = array('var','assign');

    /**
     * Compiles code for the {eval} tag
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @return string compiled code
     */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $this->required_attributes = array('var');
        $this->optional_attributes = array('assign');
        // check and get attributes
        $_attr = $this->_get_attributes($args);
        if (isset($_attr['assign'])) {
              // output will be stored in a smarty variable instead of beind displayed
            $_assign = $_attr['assign'];
        }

        // create template object
        $_output = "\$_template = new {$compiler->smarty->template_class}('eval:'.".$_attr['var'].", \$_smarty_tpl->smarty, \$_smarty_tpl);";
        //was there an assign attribute?
        if (isset($_assign)) {
            $_output .= "\$_smarty_tpl->assign($_assign,\$_template->getRenderedTemplate());";
        } else {
            $_output .= "echo \$_template->getRenderedTemplate();";
        }
        return "<?php $_output ?>";
    }
}

?>