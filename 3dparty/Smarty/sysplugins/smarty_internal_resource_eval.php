<?php

/**
 * Smarty Internal Plugin Resource Eval
 *
 * Implements the strings as resource for Smarty template
 *
 * @package Smarty
 * @subpackage TemplateResources
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Resource Eval
 */
class Smarty_Internal_Resource_Eval {
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
    public $isEvaluated = true;

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
        // no filepath for evaluated strings
        // return "string" for compiler error messages
        return 'eval:';
    }

    /**
     * Get timestamp to template source
     *
     * @param object $_template template object
     * @return boolean false as string resources have no timestamp
     */
    public function getTemplateTimestamp($_template)
    {
        // evaluated strings must always be compiled and have no timestamp
        return false;
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
        // no filepath for strings
        return false;
    }
}
?>