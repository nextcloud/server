<?php

/**
 * Smarty Internal Plugin Compile Include PHP
 *
 * Compiles the {include_php} tag
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Insert Class
 */
class Smarty_Internal_Compile_Include_Php extends Smarty_Internal_CompileBase {
	// attribute definitions
    public $required_attributes = array('file');
   	public $shorttag_order = array('file');
    public $optional_attributes = array('once', 'assign');

    /**
     * Compiles code for the {include_php} tag
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @return string compiled code
     */
    public function compile($args, $compiler)
    {
    	if (!$compiler->smarty->allow_php_tag) {
        	throw new SmartyException("{include_php} is deprecated, set allow_php_tag = true to enable");
    	}
        $this->compiler = $compiler;
        // check and get attributes
        $_attr = $this->_get_attributes($args);

        $_output = '<?php ';

        $_smarty_tpl = $compiler->template;
        $_filepath = false;
        eval('$_file = ' . $_attr['file'] . ';');
        if (!isset($this->compiler->smarty->security_policy) && file_exists($_file)) {
        	$_filepath = $_file;
        } else {
            if (isset($this->compiler->smarty->security_policy)) {
                $_dir = $this->compiler->smarty->security_policy->trusted_dir;
            } else {
                $_dir = $this->compiler->smarty->trusted_dir;
            }
            if (!empty($_dir)) {
                foreach((array)$_dir as $_script_dir) {
                    if (strpos('/\\', substr($_script_dir, -1)) === false) {
                        $_script_dir .= DS;
                    }
                    if (file_exists($_script_dir . $_file)) {
                        $_filepath = $_script_dir .  $_file;
                        break;
                    }
                }
            }
        }
        if ($_filepath == false) {
            $this->compiler->trigger_template_error("{include_php} file '{$_file}' is not readable", $this->compiler->lex->taglineno);
        }

        if (isset($this->compiler->smarty->security_policy)) {
            $this->compiler->smarty->security_policy->isTrustedPHPDir($_filepath);
        }

        if (isset($_attr['assign'])) {
            // output will be stored in a smarty variable instead of being displayed
            $_assign = $_attr['assign'];
        }
        $_once = '_once';
        if (isset($_attr['once'])) {
            if ($_attr['once'] == 'false') {
                $_once = '';
            }
        }

        if (isset($_assign)) {
            return "<?php ob_start(); include{$_once} ('{$_filepath}'); \$_smarty_tpl->assign({$_assign},ob_get_contents()); ob_end_clean();?>";
        } else {
            return "<?php include{$_once} ('{$_filepath}');?>\n";
        }
    }
}

?>