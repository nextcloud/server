<?php
/**
* Smarty Internal Plugin Compile While
*
* Compiles the {while} tag
*
* @package Smarty
* @subpackage Compiler
* @author Uwe Tews
*/

/**
* Smarty Internal Plugin Compile While Class
*/
class Smarty_Internal_Compile_While extends Smarty_Internal_CompileBase {
    /**
    * Compiles code for the {while} tag
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
        $this->_open_tag('while', $this->compiler->nocache);

        // maybe nocache because of nocache variables
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
	            $_output .= "while (\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']['var']."]->value".$parameter['if condition']['var']['smarty_internal_index']." = ".$parameter['if condition']['value']."){?>";
            } else {
	            $_output = "<?php \$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']."] = new Smarty_Variable(\$_smarty_tpl->getVariable(".$parameter['if condition']['var'].",null,true,false)->value{$_nocache});";
	            $_output .= "while (\$_smarty_tpl->tpl_vars[".$parameter['if condition']['var']."]->value = ".$parameter['if condition']['value']."){?>";
	        }
            return $_output;
        } else {
            return "<?php while ({$parameter['if condition']}){?>";
        }
    }
}

/**
* Smarty Internal Plugin Compile Whileclose Class
*/
class Smarty_Internal_Compile_Whileclose extends Smarty_Internal_CompileBase {
    /**
    * Compiles code for the {/while} tag
    *
    * @param array $args array with attributes from parser
    * @param object $compiler compiler object
    * @return string compiled code
    */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        // must endblock be nocache?
        if ($this->compiler->nocache) {
                 $this->compiler->tag_nocache = true;
        }
        $this->compiler->nocache = $this->_close_tag(array('while'));
        return "<?php }?>";
    }
}

?>