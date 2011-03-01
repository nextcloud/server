<?php

/**
 * Smarty Internal Plugin Resource File
 *
 * Implements the file system as resource for Smarty templates
 *
 * @package Smarty
 * @subpackage TemplateResources
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Resource File
 */
class Smarty_Internal_Resource_File {
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
        if ($template->getTemplateFilepath() === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get filepath to template source
     *
     * @param object $_template template object
     * @return string filepath to template source file
     */
    public function getTemplateFilepath($_template)
    {
        $_filepath = $_template->buildTemplateFilepath ();

        if ($_filepath !== false) {
            if (is_object($_template->smarty->security_policy)) {
                $_template->smarty->security_policy->isTrustedResourceDir($_filepath);
            }
        }
        $_template->templateUid = sha1($_filepath);
        return $_filepath;
    }

    /**
     * Get timestamp to template source
     *
     * @param object $_template template object
     * @return integer timestamp of template source file
     */
    public function getTemplateTimestamp($_template)
    {
        return filemtime($_template->getTemplateFilepath());
    }

    /**
     * Read template source from file
     *
     * @param object $_template template object
     * @return string content of template source file
     */
    public function getTemplateSource($_template)
    {
        // read template file
        if (file_exists($_tfp = $_template->getTemplateFilepath())) {
            $_template->template_source = file_get_contents($_tfp);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get filepath to compiled template
     *
     * @param object $_template template object
     * @return string return path to compiled template
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
        return $_compile_dir . $_filepath . '.' . $_template->resource_type . '.' . basename($_template->resource_name) . $_cache . '.php';
    }
}

?>