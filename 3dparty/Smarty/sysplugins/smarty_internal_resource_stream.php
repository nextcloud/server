<?php

/**
 * Smarty Internal Plugin Resource Stream
 *
 * Implements the streams as resource for Smarty template
 *
 * @package Smarty
 * @subpackage TemplateResources
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Resource Stream
 */
class Smarty_Internal_Resource_Stream {
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
        if ($template->getTemplateSource() == '') {
            return false;
        } else {
            return true;
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
        // no filepath for strings
        // return resource name for compiler error messages
        return str_replace(':', '://', $_template->template_resource);
    }

    /**
     * Get timestamp to template source
     *
     * @param object $_template template object
     * @return boolean false as string resources have no timestamp
     */
    public function getTemplateTimestamp($_template)
    {
        // strings must always be compiled and have no timestamp
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
        $_template->template_source = '';
        $fp = fopen(str_replace(':', '://', $_template->template_resource),'r+');
        while (!feof($fp)) {
            $_template->template_source .= fgets($fp);
        }
        fclose($fp);

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