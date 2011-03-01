<?php
/**
 * Smarty Internal Plugin Compile Print Expression
 *
 * Compiles any tag which will output an expression or variable
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Print Expression Class
 */
class Smarty_Internal_Compile_Private_Print_Expression extends Smarty_Internal_CompileBase {
	// attribute definitions
    public $optional_attributes = array('assign');
    public $option_flags = array('nocache', 'nofilter');

    /**
     * Compiles code for gererting output from any expression
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @param array $parameter array with compilation parameter
     * @return string compiled code
     */
    public function compile($args, $compiler, $parameter)
    {
        $this->compiler = $compiler;
        // check and get attributes
        $_attr = $this->_get_attributes($args);
        // nocache option
        if ($_attr['nocache'] === true) {
            $this->compiler->tag_nocache = true;
        }
        // filter handling
        if ($_attr['nofilter'] === true) {
            $_filter = 'false';
        } else {
            $_filter = 'true';
        }
        // compiled output
        // compiled output
        if (isset($_attr['assign'])) {
            // assign output to variable
            $output = "<?php \$_smarty_tpl->assign({$_attr['assign']},{$parameter['value']});?>";
        } else {
            // display value
            if (!$_attr['nofilter'] && isset($this->compiler->smarty->registered_filters['variable'])) {
                $output = "Smarty_Internal_Filter_Handler::runFilter('variable', {$parameter['value']}, \$_smarty_tpl, {$_filter})";
            } else {
                $output = $parameter['value'];
            }
            if (!$_attr['nofilter'] && !empty($this->compiler->smarty->default_modifiers)) {
                $modifierlist = array();
                foreach ($this->compiler->smarty->default_modifiers as $key => $single_default_modifier) {
                    preg_match_all('/(\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'|"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|:|[^:]+)/', $single_default_modifier, $mod_array);
                    for ($i = 0, $count = count($mod_array[0]);$i < $count;$i++) {
                        if ($mod_array[0][$i] != ':') {
                            $modifierlist[$key][] = $mod_array[0][$i];
                        }
                    }
                }
                $output = $this->compiler->compileTag('private_modifier', array(), array('modifierlist' => $modifierlist, 'value' => $output));
            }
            if (!empty($parameter['modifierlist'])) {
                $output = $this->compiler->compileTag('private_modifier', array(), array('modifierlist' => $parameter['modifierlist'], 'value' => $output));
            }
            $this->compiler->has_output = true;
            $output = "<?php echo {$output};?>";
        }
        return $output;
    }
}

?>