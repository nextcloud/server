<?php
/**
 * Smarty Internal Plugin Compile Capture
 *
 * Compiles the {capture} tag
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Capture Class
 */
class Smarty_Internal_Compile_Capture extends Smarty_Internal_CompileBase {
	// attribute definitions
    public $shorttag_order = array('name');
    public $optional_attributes = array('name', 'assign', 'append');

    /**
     * Compiles code for the {capture} tag
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

        $buffer = isset($_attr['name']) ? $_attr['name'] : "'default'";
        $assign = isset($_attr['assign']) ? $_attr['assign'] : null;
        $append = isset($_attr['append']) ? $_attr['append'] : null;

        $this->compiler->_capture_stack[] = array($buffer, $assign, $append, $this->compiler->nocache);
        // maybe nocache because of nocache variables
        $this->compiler->nocache = $this->compiler->nocache | $this->compiler->tag_nocache;
        $_output = "<?php ob_start(); ?>";

        return $_output;
    }
}

/**
 * Smarty Internal Plugin Compile Captureclose Class
 */
class Smarty_Internal_Compile_CaptureClose extends Smarty_Internal_CompileBase {
    /**
     * Compiles code for the {/capture} tag
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
        // must endblock be nocache?
        if ($this->compiler->nocache) {
            $this->compiler->tag_nocache = true;
        }

        list($buffer, $assign, $append, $this->compiler->nocache) = array_pop($this->compiler->_capture_stack);

        $_output = "<?php ";
        if (isset($assign)) {
            $_output .= " \$_smarty_tpl->assign($assign, ob_get_contents());";
        }
        if (isset($append)) {
            $_output .= " \$_smarty_tpl->append($append, ob_get_contents());";
        }
        $_output .= " Smarty::\$_smarty_vars['capture'][$buffer]=ob_get_clean();?>";
        return $_output;
    }
}

?>