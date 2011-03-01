<?php
/**
 * Smarty Internal Plugin Compile Function
 *
 * Compiles the {function} {/function} tags
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Function Class
 */
class Smarty_Internal_Compile_Function extends Smarty_Internal_CompileBase {
	// attribute definitions
    public $required_attributes = array('name');
    public $shorttag_order = array('name');
    public $optional_attributes = array('_any');

    /**
     * Compiles code for the {function} tag
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
      * @param array $parameter array with compilation parameter
    * @return boolean true
     */
    public function compile($args, $compiler, $parameter)
    {
        $this->compiler = $compiler;
        // check and get attributes
        $_attr = $this->_get_attributes($args);

        if ($_attr['nocache'] === true) {
        	$this->compiler->trigger_template_error('nocache option not allowed', $this->compiler->lex->taglineno);
        }
		unset($_attr['nocache']);
        $save = array($_attr, $compiler->parser->current_buffer,
            $compiler->template->has_nocache_code, $compiler->template->required_plugins);
        $this->_open_tag('function', $save);
        $_name = trim($_attr['name'], "'\"");
        unset($_attr['name']);
        $compiler->template->properties['function'][$_name]['parameter'] = array();
		$_smarty_tpl = $compiler->template;
        foreach ($_attr as $_key => $_data) {
        	eval ('$tmp='.$_data.';');
            $compiler->template->properties['function'][$_name]['parameter'][$_key] = $tmp;
        }
        $compiler->smarty->template_functions[$_name]['parameter'] = $compiler->template->properties['function'][$_name]['parameter'];
        if ($compiler->template->caching) {
            $output = '';
        } else {
            $output = "<?php if (!function_exists('smarty_template_function_{$_name}')) {
    function smarty_template_function_{$_name}(\$_smarty_tpl,\$params) {
    \$saved_tpl_vars = \$_smarty_tpl->tpl_vars;
    foreach (\$_smarty_tpl->template_functions['{$_name}']['parameter'] as \$key => \$value) {\$_smarty_tpl->tpl_vars[\$key] = new Smarty_variable(\$value);};
    foreach (\$params as \$key => \$value) {\$_smarty_tpl->tpl_vars[\$key] = new Smarty_variable(\$value);}?>";
        }
        // Init temporay context
        $compiler->template->required_plugins = array('compiled' => array(), 'nocache' => array());
        $compiler->parser->current_buffer = new _smarty_template_buffer($compiler->parser);
        $compiler->parser->current_buffer->append_subtree(new _smarty_tag($compiler->parser, $output));
        $compiler->template->has_nocache_code = false;
        $compiler->has_code = false;
        $compiler->template->properties['function'][$_name]['compiled'] = '';
        return true;
    }
}

/**
 * Smarty Internal Plugin Compile Functionclose Class
 */
class Smarty_Internal_Compile_Functionclose extends Smarty_Internal_CompileBase {
    /**
     * Compiles code for the {/function} tag
     *
     * @param array $args array with attributes from parser
     * @param object $compiler compiler object
     * @param array $parameter array with compilation parameter
     * @return boolean true
     */
    public function compile($args, $compiler, $parameter)
    {
        $this->compiler = $compiler;
        $_attr = $this->_get_attributes($args);
        $saved_data = $this->_close_tag(array('function'));
        $_name = trim($saved_data[0]['name'], "'\"");
        // build plugin include code
        $plugins_string = '';
        if (!empty($compiler->template->required_plugins['compiled'])) {
            $plugins_string = '<?php ';
            foreach($compiler->template->required_plugins['compiled'] as $tmp) {
                foreach($tmp as $data) {
                    $plugins_string .= "if (!is_callable('{$data['function']}')) include '{$data['file']}';\n";
                }
            }
            $plugins_string .= '?>';
        }
        if (!empty($compiler->template->required_plugins['nocache'])) {
            $plugins_string .= "<?php echo '/*%%SmartyNocache:{$compiler->template->properties['nocache_hash']}%%*/<?php ";
            foreach($compiler->template->required_plugins['nocache'] as $tmp) {
                foreach($tmp as $data) {
                    $plugins_string .= "if (!is_callable(\'{$data['function']}\')) include \'{$data['file']}\';\n";
                }
            }
            $plugins_string .= "?>/*/%%SmartyNocache:{$compiler->template->properties['nocache_hash']}%%*/';?>\n";
        }
 		// remove last line break from function definition
 		$last = count($compiler->parser->current_buffer->subtrees) - 1;
 		if ($compiler->parser->current_buffer->subtrees[$last] instanceof _smarty_linebreak) {
 			unset($compiler->parser->current_buffer->subtrees[$last]);
 		}
        // if caching save template function for possible nocache call
        if ($compiler->template->caching) {
            $compiler->template->properties['function'][$_name]['compiled'] .= $plugins_string
             . $compiler->parser->current_buffer->to_smarty_php();
            $compiler->template->properties['function'][$_name]['nocache_hash'] = $compiler->template->properties['nocache_hash'];
            $compiler->template->properties['function'][$_name]['has_nocache_code'] = $compiler->template->has_nocache_code;
            $compiler->smarty->template_functions[$_name] = $compiler->template->properties['function'][$_name];
            $compiler->has_code = false;
            $output = true;
        } else {
            $output = $plugins_string . $compiler->parser->current_buffer->to_smarty_php() . "<?php \$_smarty_tpl->tpl_vars = \$saved_tpl_vars;}}?>\n";
        }
        // restore old compiler status
        $compiler->parser->current_buffer = $saved_data[1];
        $compiler->template->has_nocache_code = $compiler->template->has_nocache_code | $saved_data[2];
        $compiler->template->required_plugins = $saved_data[3];
        return $output;
    }
}

?>