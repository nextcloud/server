<?php

/**
 * Smarty Internal Plugin Resource String
 *
 * Implements the strings as resource for Smarty template
 *
 * @package Smarty
 * @subpackage TemplateResources
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Resource String
 */
class Smarty_Internal_Resource_String {
    public function __construct($smarty)
    {
        $this->smarty = $smarty;
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
    public function isExisting($template)
    {
        return true;
    }

    /**
     * Get filepath to template source
     *
     * @param object $_template template object
     * @return string return 'string' as template source is not a file
     */
    public function getTemplateFilepath($_template)
    {
        $_template->templateUid = sha1($_template->resource_name);
        // no filepath for strings
        // return "string" for compiler error messages
        return 'string:';
    }

    /**
     * Get timestamp to template source
     *
     * @param object $_template template object
     * @return boolean false as string resources have no timestamp
     */
    public function getTemplateTimestamp($_template)
    {
        if ($this->isEvaluated) {
        	//must always be compiled and have no timestamp
        	return false;
        } else {
        	return 0;
        }
    }

    /**
     * Get timestamp of template source by type and name
     *
     * @param object $_template template object
     * @return int  timestamp (always 0)
     */
    public function getTemplateTimestampTypeName($_resource_type, $_resource_name)
    {
        // return timestamp 0
        return 0;
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
        $_template->template_source = $_template->resource_name;
        return true;
    }

    /**
     * Get filepath to compiled template
     *
     * @param object $_template template object
     * @return boolean return false as compiled template is not stored
     */
    public function getCompiledFilepath($_template)
    {
        $_compile_id = isset($_template->compile_id) ? preg_replace('![^\w\|]+!', '_', $_template->compile_id) : null;
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
        return $_compile_dir . $_filepath . '.' . $_template->resource_type . $_cache . '.php';
    }
}

?>