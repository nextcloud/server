<?php

/**
 * Smarty Internal Plugin Resource PHP
 *
 * Implements the file system as resource for PHP templates
 *
 * @package Smarty
 * @subpackage TemplateResources
 * @author Uwe Tews
 */

/**
 * Smarty Internal Plugin Resource PHP
 */
class Smarty_Internal_Resource_PHP {
    /**
     * Class constructor, enable short open tags
     */
    public function __construct($smarty)
    {
        $this->smarty = $smarty;
        ini_set('short_open_tag', '1');
    }
    // properties
    public $usesCompiler = false;
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

        if (is_object($_template->smarty->security_policy)) {
            $_template->smarty->security_policy->isTrustedResourceDir($_filepath);
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
     * @return boolean return false as compiled template is not stored
     */
    public function getCompiledFilepath($_template)
    {
        // no filepath for PHP templates
        return false;
    }

    /**
     * renders the PHP template
     */
    public function renderUncompiled($_smarty_template)
    {
        if (!$this->smarty->allow_php_templates) {
            throw new SmartyException("PHP templates are disabled");
        }
        if ($this->getTemplateFilepath($_smarty_template) === false) {
            throw new SmartyException("Unable to load template \"{$_smarty_template->resource_type} : {$_smarty_template->resource_name}\"");
        }
        // prepare variables
        $_smarty_ptr = $_smarty_template;
        do {
            foreach ($_smarty_ptr->tpl_vars as $_smarty_var => $_smarty_var_object) {
                if (isset($_smarty_var_object->value)) {
                    $$_smarty_var = $_smarty_var_object->value;
                }
            }
            $_smarty_ptr = $_smarty_ptr->parent;
        } while ($_smarty_ptr != null);
        unset ($_smarty_var, $_smarty_var_object, $_smarty_ptr);
        // include PHP template
        include($this->getTemplateFilepath($_smarty_template));
        return;
    }
}

?>