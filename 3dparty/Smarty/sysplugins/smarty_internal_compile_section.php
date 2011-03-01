<?php
/**
 * Smarty Internal Plugin Compile Section
 *
 * Compiles the {section} {sectionelse} {/section} tags
 *
 * @package Smarty
 * @subpackage Compiler
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Section Class
 */
class Smarty_Internal_Compile_Section extends Smarty_Internal_CompileBase {
	// attribute definitions
    public $required_attributes = array('name', 'loop');
   	public $shorttag_order = array('name', 'loop');
    public $optional_attributes = array('start', 'step', 'max', 'show');

    /**
     * Compiles code for the {section} tag
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

        $this->_open_tag('section', array('section',$this->compiler->nocache));
        // maybe nocache because of nocache variables
        $this->compiler->nocache = $this->compiler->nocache | $this->compiler->tag_nocache;

        $output = "<?php ";

        $section_name = $_attr['name'];

        $output .= "unset(\$_smarty_tpl->tpl_vars['smarty']->value['section'][$section_name]);\n";
        $section_props = "\$_smarty_tpl->tpl_vars['smarty']->value['section'][$section_name]";

        foreach ($_attr as $attr_name => $attr_value) {
            switch ($attr_name) {
                case 'loop':
                    $output .= "{$section_props}['loop'] = is_array(\$_loop=$attr_value) ? count(\$_loop) : max(0, (int)\$_loop); unset(\$_loop);\n";
                    break;

                case 'show':
                    if (is_bool($attr_value))
                        $show_attr_value = $attr_value ? 'true' : 'false';
                    else
                        $show_attr_value = "(bool)$attr_value";
                    $output .= "{$section_props}['show'] = $show_attr_value;\n";
                    break;

                case 'name':
                    $output .= "{$section_props}['$attr_name'] = $attr_value;\n";
                    break;

                case 'max':
                case 'start':
                    $output .= "{$section_props}['$attr_name'] = (int)$attr_value;\n";
                    break;

                case 'step':
                    $output .= "{$section_props}['$attr_name'] = ((int)$attr_value) == 0 ? 1 : (int)$attr_value;\n";
                    break;
            }
        }

        if (!isset($_attr['show']))
            $output .= "{$section_props}['show'] = true;\n";

        if (!isset($_attr['loop']))
            $output .= "{$section_props}['loop'] = 1;\n";

        if (!isset($_attr['max']))
            $output .= "{$section_props}['max'] = {$section_props}['loop'];\n";
        else
            $output .= "if ({$section_props}['max'] < 0)\n" . "    {$section_props}['max'] = {$section_props}['loop'];\n";

        if (!isset($_attr['step']))
            $output .= "{$section_props}['step'] = 1;\n";

        if (!isset($_attr['start']))
            $output .= "{$section_props}['start'] = {$section_props}['step'] > 0 ? 0 : {$section_props}['loop']-1;\n";
        else {
            $output .= "if ({$section_props}['start'] < 0)\n" . "    {$section_props}['start'] = max({$section_props}['step'] > 0 ? 0 : -1, {$section_props}['loop'] + {$section_props}['start']);\n" . "else\n" . "    {$section_props}['start'] = min({$section_props}['start'], {$section_props}['step'] > 0 ? {$section_props}['loop'] : {$section_props}['loop']-1);\n";
        }

        $output .= "if ({$section_props}['show']) {\n";
        if (!isset($_attr['start']) && !isset($_attr['step']) && !isset($_attr['max'])) {
            $output .= "    {$section_props}['total'] = {$section_props}['loop'];\n";
        } else {
            $output .= "    {$section_props}['total'] = min(ceil(({$section_props}['step'] > 0 ? {$section_props}['loop'] - {$section_props}['start'] : {$section_props}['start']+1)/abs({$section_props}['step'])), {$section_props}['max']);\n";
        }
        $output .= "    if ({$section_props}['total'] == 0)\n" . "        {$section_props}['show'] = false;\n" . "} else\n" . "    {$section_props}['total'] = 0;\n";

        $output .= "if ({$section_props}['show']):\n";
        $output .= "
            for ({$section_props}['index'] = {$section_props}['start'], {$section_props}['iteration'] = 1;
                 {$section_props}['iteration'] <= {$section_props}['total'];
                 {$section_props}['index'] += {$section_props}['step'], {$section_props}['iteration']++):\n";
        $output .= "{$section_props}['rownum'] = {$section_props}['iteration'];\n";
        $output .= "{$section_props}['index_prev'] = {$section_props}['index'] - {$section_props}['step'];\n";
        $output .= "{$section_props}['index_next'] = {$section_props}['index'] + {$section_props}['step'];\n";
        $output .= "{$section_props}['first']      = ({$section_props}['iteration'] == 1);\n";
        $output .= "{$section_props}['last']       = ({$section_props}['iteration'] == {$section_props}['total']);\n";

        $output .= "?>";
        return $output;
    }
}

/**
* Smarty Internal Plugin Compile Sectionelse Class
*/
class Smarty_Internal_Compile_Sectionelse extends Smarty_Internal_CompileBase {
    /**
     * Compiles code for the {sectionelse} tag
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

        list($_open_tag, $nocache) = $this->_close_tag(array('section'));
        $this->_open_tag('sectionelse',array('sectionelse', $nocache));

        return "<?php endfor; else: ?>";
    }
}

/**
 * Smarty Internal Plugin Compile Sectionclose Class
 */
class Smarty_Internal_Compile_Sectionclose extends Smarty_Internal_CompileBase {
    /**
     * Compiles code for the {/section} tag
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

        list($_open_tag, $this->compiler->nocache) = $this->_close_tag(array('section', 'sectionelse'));

        if ($_open_tag == 'sectionelse')
            return "<?php endif; ?>";
        else
            return "<?php endfor; endif; ?>";
    }
}

?>