<?php
/**
 * Smarty Internal Plugin Compile If
 *
 * Compiles the {if} {else} {elseif} {/if} tags
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile If Class
 */
class Smarty_Internal_Compile_If extends Smarty_Internal_CompileBase {
    /**
     * Compiles code for the {if} tag
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
        $this->_open_tag('if',array(1,$this->compiler->nocache));
        // must whole block be nocache ?
        $this->compiler->nocache = $this->compiler->nocache | $this->compiler->tag_nocache;
        if (is_array($parameter['if condition'])) {
        	if ($this->compiler->nocache) {
        		$_nocache = ',true';
            	// create nocache var to make it know for further compiling
            	if (is_array($parameter['if condition']['var'])) {
            		$this->compiler->template->tpl_vars[trim($parameter['if condition']['var']['var'], "'")] = new Smarty_variable(null, true);
            	} else {
            		$this->compiler->template->tpl_vars[trim($parameter['if condition']['var'], "'")] = new Smarty_variable(null, true);
            	}
        	} else {
        		$_nocache = '';
        	}
            if (is_array($parameter['if condition']['var'])) {
            	$_output = "<?php if (!isset(\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]) || !is_array(\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]->value)) \$_smarty_tpl->createLocalArrayVariable(".$parameter['if condition']['var']['var']."$_nocache);\n";
	            $_output .= "if (\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]->value".$parameter['if condition']['var']['smarty_internal_index']." = ".$parameter['if condition']['value']."){?>";
            } else {
	            $_output = "<?php \$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']."] = new Smarty_Variable(\$_smarty_tpl->getVariable(".$parameter['if condition']['var'].",null,true,false)->value{$_nocache});";
	            $_output .= "if (\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']."]->value = ".$parameter['if condition']['value']."){?>";
	        }
            return $_output;
        } else {
            return "<?php if ({$parameter['if condition']}){?>";
        }
    }
}

/**
 * Smarty Internal Plugin Compile Else Class
 */
class Smarty_Internal_Compile_Else extends Smarty_Internal_CompileBase {
    /**
     * Compiles code for the {else} tag
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @param array $parameter array with compilation parameter
     * @return string compiled code
     */
    public function compile($args, $compiler, $parameter)
    {
        $this->compiler = $compiler;
        list($nesting, $compiler->tag_nocache) = $this->_close_tag(array('if', 'elseif'));
        $this->_open_tag('else',array($nesting,$compiler->tag_nocache));

        return "<?php }else{ ?>";
    }
}

/**
 * Smarty Internal Plugin Compile ElseIf Class
 */
class Smarty_Internal_Compile_Elseif extends Smarty_Internal_CompileBase {
    /**
     * Compiles code for the {elseif} tag
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

        list($nesting, $compiler->tag_nocache) = $this->_close_tag(array('if', 'elseif'));

		if (is_array($parameter['if condition'])) {
			$condition_by_assign = true;
			if ($this->compiler->nocache) {
        		$_nocache = ',true';
				// create nocache var to make it know for further compiling
				if (is_array($parameter['if condition']['var'])) {
					$this->compiler->template->tpl_vars[trim($parameter['if condition']['var']['var'], "'")] = new Smarty_variable(null, true);
				} else {
					$this->compiler->template->tpl_vars[trim($parameter['if condition']['var'], "'")] = new Smarty_variable(null, true);
				}
			} else {
				$_nocache = '';
			}
		} else {
			$condition_by_assign = false;
		}

        if (empty($this->compiler->prefix_code)) {
        	if ($condition_by_assign) {
            	$this->_open_tag('elseif', array($nesting + 1, $compiler->tag_nocache));
            	if (is_array($parameter['if condition']['var'])) {
            		$_output = "<?php }else{ if (!isset(\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]) || !is_array(\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]->value)) \$_smarty_tpl->createLocalArrayVariable(".$parameter['if condition']['var']['var']."$_nocache);\n";
	            	$_output .= "if (\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]->value".$parameter['if condition']['var']['smarty_internal_index']." = ".$parameter['if condition']['value']."){?>";
            	} else {
	            	$_output = "<?php }else{ \$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']."] = new Smarty_Variable(\$_smarty_tpl->getVariable(".$parameter['if condition']['var'].",null,true,false)->value{$_nocache});";
	            	$_output .= "if (\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']."]->value = ".$parameter['if condition']['value']."){?>";
				}
            	return $_output;
        	} else {
            	$this->_open_tag('elseif', array($nesting, $compiler->tag_nocache));
            	return "<?php }elseif({$parameter['if condition']}){?>";
        	}
        } else {
            $tmp = '';
            foreach ($this->compiler->prefix_code as $code) $tmp .= $code;
            $this->compiler->prefix_code = array();
            $this->_open_tag('elseif', array($nesting + 1, $compiler->tag_nocache));
        	if ($condition_by_assign) {
            	if (is_array($parameter['if condition']['var'])) {
            		$_output = "<?php }else{?>{$tmp}<?php  if (!isset(\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]) || !is_array(\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]->value)) \$_smarty_tpl->createLocalArrayVariable(".$parameter['if condition']['var']['var']."$_nocache);\n";
	            	$_output .= "if (\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]->value".$parameter['if condition']['var']['smarty_internal_index']." = ".$parameter['if condition']['value']."){?>";
            	} else {
	            	$_output = "<?php }else{?>{$tmp}<?php \$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']."] = new Smarty_Variable(\$_smarty_tpl->getVariable(".$parameter['if condition']['var'].",null,true,false)->value{$_nocache});";
	            	$_output .= "if (\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']."]->value = ".$parameter['if condition']['value']."){?>";
				}
            	return $_output;
        	} else {
            	return "<?php }else{?>{$tmp}<?php if ({$parameter['if condition']}){?>";
        	}
        }
    }
}

/**
* Smarty Internal Plugin Compile Ifclose Class
*/
class Smarty_Internal_Compile_Ifclose extends Smarty_Internal_CompileBase {
    /**
    * Compiles code for the {/if} tag
    *
    * @param array $args array with attributes from parser
    * @param object $compiler compiler object
     * @param array $parameter array with compilation parameter
    * @return string compiled code
    */
    public function compile($args, $compiler, $parameter)
    {
        $this->compiler = $compiler;
            // must endblock be nocache?
            if ($this->compiler->nocache) {
                $this->compiler->tag_nocache = true;
            }
        list($nesting, $this->compiler->nocache) = $this->_close_tag(array('if', 'else', 'elseif'));
        $tmp = '';
        for ($i = 0; $i < $nesting ; $i++) $tmp .= '}';
        return "<?php {$tmp}?>";
    }
}

?>