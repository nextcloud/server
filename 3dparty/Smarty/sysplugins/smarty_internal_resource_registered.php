<?php

/**
 * Smarty Internal Plugin Resource Registered
 *
 * Implements the registered resource for Smarty template
 *
 * @package Smarty
 * @subpackage TemplateResources
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Resource Registered
 */
class Smarty_Internal_Resource_Registered {
    public function __construct($template, $resource_type = null)
    {
        $this->smarty = $template->smarty;
        if (isset($resource_type)) {
        	$template->smarty->registerResource($resource_type,
        		array("smarty_resource_{$resource_type}_source",
            		"smarty_resource_{$resource_type}_timestamp",
                	"smarty_resource_{$resource_type}_secure",
                	"smarty_resource_{$resource_type}_trusted"));
        }
    }
    // classes used for compiling Smarty templates from file resource
    public $compiler_class = 'Smarty_Internal_SmartyTemplateCompiler';
    public $template_lexer_class = 'Smarty_Internal_Templatelexer';
    public $template_parser_class = 'Smarty_Internal_Templateparser';
    // properties
    public $usesCompiler = true;
    public $isEvaluated = false;

    /**
     * Return flag if template source is existing
     *
     * @return boolean true
     */
    public function isExisting($_template)
    {
        if (is_integer($_template->getTemplateTimestamp())) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Get filepath to template source
     *
     * @param object $_template template object
     * @return string return 'string' as template source is not a file
     */
    public function getTemplateFilepath($_template)
    {
        $_filepath = $_template->resource_type .':'.$_template->resource_name;
        $_template->templateUid = sha1($_filepath);
        return $_filepath;
    }

    /**
     * Get timestamp of template source
     *
     * @param object $_template template object
     * @return int  timestamp
     */
    public function getTemplateTimestamp($_template)
    {
        // return timestamp
        $time_stamp = false;
        call_user_func_array($this->smarty->registered_resources[$_template->resource_type][0][1],
            array($_template->resource_name, &$time_stamp, $this->smarty));
        return is_numeric($time_stamp) ? (int)$time_stamp : $time_stamp;
    }

    /**
     * Get timestamp of template source by type and name
     *
     * @param object $_template template object
     * @return int  timestamp
     */
    public function getTemplateTimestampTypeName($_resource_type, $_resource_name)
    {
        // return timestamp
        $time_stamp = false;
        call_user_func_array($this->smarty->registered_resources[$_resource_type][0][1],
            array($_resource_name, &$time_stamp, $this->smarty));
        return is_numeric($time_stamp) ? (int)$time_stamp : $time_stamp;
    }

    /**
     * Retuen template source from resource name
     *
     * @param object $_template template object
     * @return string content of template source
     */
    public function getTemplateSource($_template)
    {
        // return template string
        return call_user_func_array($this->smarty->registered_resources[$_template->resource_type][0][0],
            array($_template->resource_name, &$_template->template_source, $this->smarty));
    }

    /**
     * Get filepath to compiled template
     *
     * @param object $_template template object
     * @return boolean return false as compiled template is not stored
     */
    public function getCompiledFilepath($_template)
    {
        $_compile_id =  isset($_template->compile_id) ? preg_replace('![^\w\|]+!','_',$_template->compile_id) : null;
        // calculate Uid if not already done
        if ($_template->templateUid == '') {
            $_template->getTemplateFilepath();
        }
        $_filepath = $_template->templateUid;
        // if use_sub_dirs, break file into directories
        if ($_template->smarty->use_sub_dirs) {
            $_filepath = substr($_filepath, 0, 2) . DS
             . substr($_filepath, 2, 2) . DS
             . substr($_filepath, 4, 2) . DS
             . $_filepath;
        }
        $_compile_dir_sep = $_template->smarty->use_sub_dirs ? DS : '^';
        if (isset($_compile_id)) {
            $_filepath = $_compile_id . $_compile_dir_sep . $_filepath;
        }
        if ($_template->caching) {
            $_cache = '.cache';
        } else {
            $_cache = '';
        }
        $_compile_dir = $_template->smarty->compile_dir;
        if (strpos('/\\', substr($_compile_dir, -1)) === false) {
            $_compile_dir .= DS;
        }
        return $_compile_dir . $_filepath . '.' . $_template->resource_type . '.' . basename($_template->resource_name) . $_cache . '.php';
    }
}

?>