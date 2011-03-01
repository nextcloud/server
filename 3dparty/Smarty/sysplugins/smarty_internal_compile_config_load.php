<?php

/**
 * Smarty Internal Plugin Compile Config Load
 *
 * Compiles the {config load} tag
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Config Load Class
 */
class Smarty_Internal_Compile_Config_Load extends Smarty_Internal_CompileBase {
	// attribute definitions
    public $required_attributes = array('file');
    public $shorttag_order = array('file','section');
    public $optional_attributes = array('section', 'scope');

    /**
     * Compiles code for the {config_load} tag
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @return string compiled code
     */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        // check and get attributes
        $_attr = $this->_get_attributes($args);

        if ($_attr['nocache'] === true) {
        	$this->compiler->trigger_template_error('nocache option not allowed', $this->compiler->lex->taglineno);
        }


        // save posible attributes
        $conf_file = $_attr['file'];
        if (isset($_attr['section'])) {
            $section = $_attr['section'];
        } else {
            $section = 'null';
        }
        $scope = 'local';
        // scope setup
        if (isset($_attr['scope'])) {
            $_attr['scope'] = trim($_attr['scope'], "'\"");
            if (in_array($_attr['scope'],array('local','parent','root','global'))) {
                $scope = $_attr['scope'];
           } else {
                $this->compiler->trigger_template_error('illegal value for "scope" attribute', $this->compiler->lex->taglineno);
           }
        }
        // create config object
        $_output = "<?php  \$_config = new Smarty_Internal_Config($conf_file, \$_smarty_tpl->smarty, \$_smarty_tpl);";
        $_output .= "\$_config->loadConfigVars($section, '$scope'); ?>";
        return $_output;
    }
}

?>