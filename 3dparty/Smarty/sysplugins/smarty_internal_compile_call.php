<?php

/**
 * Smarty Internal Plugin Compile Function_Call
 *
 * Compiles the calls of user defined tags defined by {function}
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Function_Call Class
 */
class Smarty_Internal_Compile_Call extends Smarty_Internal_CompileBase {
	// attribute definitions
    public $required_attributes = array('name');
    public $shorttag_order = array('name');
    public $optional_attributes = array('_any');

    /**
     * Compiles the calls of user defined tags defined by {function}
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @param array $parameter array with compilation parameter
     * @return string compiled code
     */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $this->smarty = $compiler->smarty;
        // check and get attributes
        $_attr = $this->_get_attributes($args);
        // save possible attributes
        if (isset($_attr['assign'])) {
            // output will be stored in a smarty variable instead of beind displayed
            $_assign = $_attr['assign'];
        }
        $_name = $_attr['name'];
        unset($_attr['name'], $_attr['assign'], $_attr['nocache']);
        // set flag (compiled code of {function} must be included in cache file
        if ($compiler->nocache || $compiler->tag_nocache) {
            $_nocache = 'true';
        } else {
            $_nocache = 'false';
        }
        $_paramsArray = array();
        foreach ($_attr as $_key => $_value) {
            if (is_int($_key)) {
                $_paramsArray[] = "$_key=>$_value";
            } else {
                $_paramsArray[] = "'$_key'=>$_value";
            }
        }
        if (isset($compiler->template->properties['function'][$_name]['parameter'])) {
            foreach ($compiler->template->properties['function'][$_name]['parameter'] as $_key => $_value) {
                if (!isset($_attr[$_key])) {
                    if (is_int($_key)) {
                        $_paramsArray[] = "$_key=>$_value";
                    } else {
                        $_paramsArray[] = "'$_key'=>$_value";
                    }
                }
            }
        } elseif (isset($this->smarty->template_functions[$_name]['parameter'])) {
           foreach ($this->smarty->template_functions[$_name]['parameter'] as $_key => $_value) {
                if (!isset($_attr[$_key])) {
                    if (is_int($_key)) {
                        $_paramsArray[] = "$_key=>$_value";
                    } else {
                        $_paramsArray[] = "'$_key'=>$_value";
                    }
                }
            }
        }
        //varibale name?
        if (!(strpos($_name,'$')===false)) {
        	$call_cache = $_name;
        	$call_function = '$tmp = "smarty_template_function_".'.$_name.'; $tmp';
       	} else {
        	$_name = trim($_name, "'\"");
       		$call_cache = "'{$_name}'";
         	$call_function = 'smarty_template_function_'.$_name;
        }

        $_params = 'array(' . implode(",", $_paramsArray) . ')';
    	$_hash = str_replace('-','_',$compiler->template->properties['nocache_hash']);
        // was there an assign attribute
        if (isset($_assign)) {
            if ($compiler->template->caching) {
                $_output = "<?php ob_start(); Smarty_Internal_Function_Call_Handler::call ({$call_cache},\$_smarty_tpl,{$_params},'{$_hash}',{$_nocache}); \$_smarty_tpl->assign({$_assign}, ob_get_clean());?>\n";
            } else {
                $_output = "<?php ob_start(); {$call_function}(\$_smarty_tpl,{$_params}); \$_smarty_tpl->assign({$_assign}, ob_get_clean());?>\n";
            }
        } else {
            if ($compiler->template->caching) {
                $_output = "<?php Smarty_Internal_Function_Call_Handler::call ({$call_cache},\$_smarty_tpl,{$_params},'{$_hash}',{$_nocache});?>\n";
            } else {
                $_output = "<?php {$call_function}(\$_smarty_tpl,{$_params});?>\n";
            }
        }
        return $_output;
    }
}

?>