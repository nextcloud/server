<?php

/**
 * Smarty Internal Plugin Compile extend
 *
 * Compiles the {extends} tag
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile extend Class
 */
class Smarty_Internal_Compile_Extends extends Smarty_Internal_CompileBase {
	// attribute definitions
    public $required_attributes = array('file');
    public $shorttag_order = array('file');

    /**
     * Compiles code for the {extends} tag
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @return string compiled code
     */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $this->smarty = $compiler->smarty;
        $this->_rdl = preg_quote($this->smarty->right_delimiter);
        $this->_ldl = preg_quote($this->smarty->left_delimiter);
        $filepath = $compiler->template->getTemplateFilepath();
        // check and get attributes
        $_attr = $this->_get_attributes($args);
        if ($_attr['nocache'] === true) {
        	$this->compiler->trigger_template_error('nocache option not allowed', $this->compiler->lex->taglineno);
        }

        $_smarty_tpl = $compiler->template;
        $include_file = null;
        if (strpos($_attr['file'],'$_tmp') !== false) {
        	$this->compiler->trigger_template_error('illegal value for file attribute', $this->compiler->lex->taglineno);
        }
        eval('$include_file = ' . $_attr['file'] . ';');
        // create template object
        $_template = new $compiler->smarty->template_class($include_file, $this->smarty, $compiler->template);
        // save file dependency
        if (in_array($_template->resource_type,array('eval','string'))) {
        	$template_sha1 = sha1($include_file);
    	} else {
        	$template_sha1 = sha1($_template->getTemplateFilepath());
    	}
        if (isset($compiler->template->properties['file_dependency'][$template_sha1])) {
            $this->compiler->trigger_template_error("illegal recursive call of \"{$include_file}\"",$compiler->lex->line-1);
        }
        $compiler->template->properties['file_dependency'][$template_sha1] = array($_template->getTemplateFilepath(), $_template->getTemplateTimestamp(),$_template->resource_type);
        $_content = substr($compiler->template->template_source,$compiler->lex->counter-1);
        if (preg_match_all("!({$this->_ldl}block\s(.+?){$this->_rdl})!", $_content, $s) !=
                preg_match_all("!({$this->_ldl}/block{$this->_rdl})!", $_content, $c)) {
            $this->compiler->trigger_template_error('unmatched {block} {/block} pairs');
        }
        preg_match_all("!{$this->_ldl}block\s(.+?){$this->_rdl}|{$this->_ldl}/block{$this->_rdl}!", $_content, $_result, PREG_OFFSET_CAPTURE);
        $_result_count = count($_result[0]);
        $_start = 0;
        while ($_start < $_result_count) {
            $_end = 0;
            $_level = 1;
            while ($_level != 0) {
                $_end++;
                if (!strpos($_result[0][$_start + $_end][0], '/')) {
                    $_level++;
                } else {
                    $_level--;
                }
            }
            $_block_content = str_replace($this->smarty->left_delimiter . '$smarty.block.parent' . $this->smarty->right_delimiter, '%%%%SMARTY_PARENT%%%%',
                substr($_content, $_result[0][$_start][1] + strlen($_result[0][$_start][0]), $_result[0][$_start + $_end][1] - $_result[0][$_start][1] - + strlen($_result[0][$_start][0])));
            Smarty_Internal_Compile_Block::saveBlockData($_block_content, $_result[0][$_start][0], $compiler->template, $filepath);
            $_start = $_start + $_end + 1;
        }
        $compiler->template->template_source = $_template->getTemplateSource();
        $compiler->template->template_filepath = $_template->getTemplateFilepath();
        $compiler->abort_and_recompile = true;
        return '';
    }

}
?>