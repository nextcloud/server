<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage Security
 * @author Uwe Tews
 */

/**
 * This class does contain the security settings
 */
class Smarty_Security {
    /**
     * This determines how Smarty handles "<?php ... ?>" tags in templates.
     * possible values:
     * <ul>
     *   <li>Smarty::PHP_PASSTHRU -> echo PHP tags as they are</li>
     *   <li>Smarty::PHP_QUOTE    -> escape tags as entities</li>
     *   <li>Smarty::PHP_REMOVE   -> remove php tags</li>
     *   <li>Smarty::PHP_ALLOW    -> execute php tags</li>
     * </ul>
     *
     * @var integer
     */
    public $php_handling = Smarty::PHP_PASSTHRU;

    /**
     * This is the list of template directories that are considered secure.
     * $template_dir is in this list implicitly.
     *
     * @var array
     */
    public $secure_dir = array();


    /**
     * This is an array of directories where trusted php scripts reside.
     * {@link $security} is disabled during their inclusion/execution.
     *
     * @var array
     */
    public $trusted_dir = array();


    /**
     * This is an array of trusted static classes.
     *
     * If empty access to all static classes is allowed.
     * If set to 'none' none is allowed.
     * @var array
     */
    public $static_classes = array();

    /**
     * This is an array of trusted PHP functions.
     *
     * If empty all functions are allowed.
     * To disable all PHP functions set $php_functions = null.
     * @var array
     */
    public $php_functions = array('isset', 'empty',
            'count', 'sizeof','in_array', 'is_array','time','nl2br');

    /**
     * This is an array of trusted PHP modifers.
     *
     * If empty all modifiers are allowed.
     * To disable all modifier set $modifiers = null.
     * @var array
     */
    public $php_modifiers = array('escape','count');

    /**
     * This is an array of trusted streams.
     *
     * If empty all streams are allowed.
     * To disable all streams set $streams = null.
     * @var array
     */
    public $streams = array('file');
    /**
     * + flag if constants can be accessed from template
     */
    public $allow_constants = true;
    /**
     * + flag if super globals can be accessed from template
     */
    public $allow_super_globals = true;
    /**
     * + flag if the {php} and {include_php} tag can be executed
     */
    public $allow_php_tag = false;

    public function __construct($smarty)
    {
        $this->smarty = $smarty;
	}
    /**
     * Check if PHP function is trusted.
     *
     * @param string $function_name
     * @param object $compiler compiler object
     * @return boolean true if function is trusted
     */
    function isTrustedPhpFunction($function_name, $compiler)
    {
        if (isset($this->php_functions) && (empty($this->php_functions) || in_array($function_name, $this->php_functions))) {
            return true;
        } else {
            $compiler->trigger_template_error ("PHP function '{$function_name}' not allowed by security setting");
            return false;
        }
    }

    /**
     * Check if static class is trusted.
     *
     * @param string $class_name
     * @param object $compiler compiler object
     * @return boolean true if class is trusted
     */
    function isTrustedStaticClass($class_name, $compiler)
    {
        if (isset($this->static_classes) && (empty($this->static_classes) || in_array($class_name, $this->static_classes))) {
            return true;
        } else {
            $compiler->trigger_template_error ("access to static class '{$class_name}' not allowed by security setting");
            return false;
        }
    }
    /**
     * Check if modifier is trusted.
     *
     * @param string $modifier_name
     * @param object $compiler compiler object
     * @return boolean true if modifier is trusted
     */
    function isTrustedModifier($modifier_name, $compiler)
    {
        if (isset($this->php_modifiers) && (empty($this->php_modifiers) || in_array($modifier_name, $this->php_modifiers))) {
            return true;
        } else {
            $compiler->trigger_template_error ("modifier '{$modifier_name}' not allowed by security setting");
            return false;
        }
    }
    /**
     * Check if stream is trusted.
     *
     * @param string $stream_name
     * @param object $compiler compiler object
     * @return boolean true if stream is trusted
     */
    function isTrustedStream($stream_name)
    {
        if (isset($this->streams) && (empty($this->streams) || in_array($stream_name, $this->streams))) {
            return true;
        } else {
            throw new SmartyException ("stream '{$stream_name}' not allowed by security setting");
            return false;
        }
    }

    /**
     * Check if directory of file resource is trusted.
     *
     * @param string $filepath
     * @param object $compiler compiler object
     * @return boolean true if directory is trusted
     */
    function isTrustedResourceDir($filepath)
    {
        $_rp = realpath($filepath);
        if (isset($this->smarty->template_dir)) {
            foreach ((array)$this->smarty->template_dir as $curr_dir) {
                if (($_cd = realpath($curr_dir)) !== false &&
                        strncmp($_rp, $_cd, strlen($_cd)) == 0 &&
                        (strlen($_rp) == strlen($_cd) || substr($_rp, strlen($_cd), 1) == DS)) {
                    return true;
                }
            }
        }
        if (!empty($this->smarty->security_policy->secure_dir)) {
            foreach ((array)$this->smarty->security_policy->secure_dir as $curr_dir) {
                if (($_cd = realpath($curr_dir)) !== false) {
                    if ($_cd == $_rp) {
                        return true;
                    } elseif (strncmp($_rp, $_cd, strlen($_cd)) == 0 &&
                            (strlen($_rp) == strlen($_cd) || substr($_rp, strlen($_cd), 1) == DS)) {
                        return true;
                    }
                }
            }
        }

        throw new SmartyException ("directory '{$_rp}' not allowed by security setting");
        return false;
    }

    /**
     * Check if directory of file resource is trusted.
     *
     * @param string $filepath
     * @param object $compiler compiler object
     * @return boolean true if directory is trusted
     */
    function isTrustedPHPDir($filepath)
    {
        $_rp = realpath($filepath);
        if (!empty($this->trusted_dir)) {
            foreach ((array)$this->trusted_dir as $curr_dir) {
                if (($_cd = realpath($curr_dir)) !== false) {
                    if ($_cd == $_rp) {
                        return true;
                    } elseif (strncmp($_rp, $_cd, strlen($_cd)) == 0 &&
                            substr($_rp, strlen($_cd), 1) == DS) {
                        return true;
                    }
                }
            }
        }

        throw new SmartyException ("directory '{$_rp}' not allowed by security setting");
        return false;
    }
}

?>