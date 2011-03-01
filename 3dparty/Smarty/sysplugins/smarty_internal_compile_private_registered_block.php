<?php
/**
 * Smarty Internal Plugin Compile Registered Block
 *
 * Compiles code for the execution of a registered block function
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Registered Block Class
 */
class Smarty_Internal_Compile_Private_Registered_Block extends Smarty_Internal_CompileBase {
	// attribute definitions
    public $optional_attributes = array('_any');

    /**
     * Compiles code for the execution of a block function
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @param array $parameter array with compilation parameter
     * @param string $tag name of block function
     * @return string compiled code
     */
    public function compile($args, $compiler, $parameter, $tag)
    {
        $this->compiler = $compiler;
        if (strlen($tag) < 6 || substr($tag,-5) != 'close') {
            // opening tag of block plugin
        	// check and get attributes
        	$_attr = $this->_get_attributes($args);
        	if ($_attr['nocache']) {
            	$this->compiler->tag_nocache = true;
        	}
       		unset($_attr['nocache']);
            // convert attributes into parameter array string
            $_paramsArray = array();
            foreach ($_attr as $_key => $_value) {
                if (is_int($_key)) {
                    $_paramsArray[] = "$_key=>$_value";
            	} elseif ($this->compiler->template->caching && in_array($_key,$compiler->smarty->registered_plugins[Smarty::PLUGIN_BLOCK][$tag][2])) {
					$_value = str_replace("'","^#^",$_value);
                	$_paramsArray[] = "'$_key'=>^#^.var_export($_value,true).^#^";
                } else {
                    $_paramsArray[] = "'$_key'=>$_value";
                }
            }
            $_params = 'array(' . implode(",", $_paramsArray) . ')';

            $this->_open_tag($tag, array($_params, $this->compiler->nocache));
            // maybe nocache because of nocache variables or nocache plugin
            $this->compiler->nocache = !$compiler->smarty->registered_plugins[Smarty::PLUGIN_BLOCK][$tag][1] | $this->compiler->nocache | $this->compiler->tag_nocache;
            $function = $compiler->smarty->registered_plugins[Smarty::PLUGIN_BLOCK][$tag][0];
            // compile code
            if (!is_array($function)) {
                $output = "<?php \$_smarty_tpl->smarty->_tag_stack[] = array('{$tag}', {$_params}); \$_block_repeat=true; {$function}({$_params}, null, \$_smarty_tpl, \$_block_repeat);while (\$_block_repeat) { ob_start();?>";
            } else if (is_object($function[0])) {
                $output = "<?php \$_smarty_tpl->smarty->_tag_stack[] = array('{$tag}', {$_params}); \$_block_repeat=true; \$_smarty_tpl->smarty->registered_plugins['block']['{$tag}'][0][0]->{$function[1]}({$_params}, null, \$_smarty_tpl, \$_block_repeat);while (\$_block_repeat) { ob_start();?>";
            } else {
                $output = "<?php \$_smarty_tpl->smarty->_tag_stack[] = array('{$tag}', {$_params}); \$_block_repeat=true; {$function[0]}::{$function[1]}({$_params}, null, \$_smarty_tpl, \$_block_repeat);while (\$_block_repeat) { ob_start();?>";
            }
        } else {
            // must endblock be nocache?
            if ($this->compiler->nocache) {
                $this->compiler->tag_nocache = true;
            }
            $base_tag = substr($tag, 0, -5);
            // closing tag of block plugin, restore nocache
            list($_params, $this->compiler->nocache) = $this->_close_tag($base_tag);
            // This tag does create output
            $this->compiler->has_output = true;
            $function = $compiler->smarty->registered_plugins[Smarty::PLUGIN_BLOCK][$base_tag][0];
            // compile code
            if (!isset($parameter['modifier_list'])) {
            	$mod_pre = $mod_post ='';
            } else {
            	$mod_pre = ' ob_start(); ';
            	$mod_post = 'echo '.$this->compiler->compileTag('private_modifier',array(),array('modifierlist'=>$parameter['modifier_list'],'value'=>'ob_get_clean()')).';';
            }
            if (!is_array($function)) {
                $output = "<?php \$_block_content = ob_get_clean(); \$_block_repeat=false;".$mod_pre." echo {$function}({$_params}, \$_block_content, \$_smarty_tpl, \$_block_repeat);".$mod_post." } array_pop(\$_smarty_tpl->smarty->_tag_stack);?>";
            } else if (is_object($function[0])) {
                $output = "<?php \$_block_content = ob_get_clean(); \$_block_repeat=false;".$mod_pre." echo \$_smarty_tpl->smarty->registered_plugins['block']['{$base_tag}'][0][0]->{$function[1]}({$_params}, \$_block_content, \$_smarty_tpl, \$_block_repeat); ".$mod_post."} array_pop(\$_smarty_tpl->smarty->_tag_stack);?>";
            } else {
                $output = "<?php \$_block_content = ob_get_clean(); \$_block_repeat=false;".$mod_pre." echo {$function[0]}::{$function[1]}({$_params}, \$_block_content, \$_smarty_tpl, \$_block_repeat); ".$mod_post."} array_pop(\$_smarty_tpl->smarty->_tag_stack);?>";
            }
        }
        return $output."\n";
    }
}

?>