<?php

/**
 * Smarty Internal Plugin Filter
 *
 * External Smarty filter methods
 *
 * @package Smarty
 * @author Uwe Tews
 */

/**
 * Class for filter methods
 */
class Smarty_Internal_Filter {

    function __construct($smarty)
    {
        $this->smarty = $smarty;
    }
    /**
     * Registers a filter function
     *
     * @param string $type filter type
     * @param callback $callback
     */
	public function registerFilter($type, $callback)
	{
   		$this->smarty->registered_filters[$type][$this->_get_filter_name($callback)] = $callback;
	}

    /**
     * Unregisters a filter function
     *
     * @param string $type filter type
     * @param callback $callback
     */
	public function unregisterFilter($type, $callback)
	{
   		$name = $this->_get_filter_name($callback);
   		if(isset($this->smarty->registered_filters[$type][$name])) {
      		unset($this->smarty->registered_filters[$type][$name]);
   		}
	}


    /**
     * Return internal filter name
     *
     * @param callback $function_name
     */
    public function _get_filter_name($function_name)
    {
        if (is_array($function_name)) {
            $_class_name = (is_object($function_name[0]) ?
                get_class($function_name[0]) : $function_name[0]);
            return $_class_name . '_' . $function_name[1];
        } else {
            return $function_name;
        }
    }


    /**
     * load a filter of specified type and name
     *
     * @param string $type filter type
     * @param string $name filter name
     * @return bool
     */
    function loadFilter($type, $name)
    {
        $_plugin = "smarty_{$type}filter_{$name}";
        $_filter_name = $_plugin;
        if ($this->smarty->loadPlugin($_plugin)) {
            if (class_exists($_plugin, false)) {
                $_plugin = array($_plugin, 'execute');
            }
            if (is_callable($_plugin)) {
                return $this->smarty->registered_filters[$type][$_filter_name] = $_plugin;
            }
        }
        throw new SmartyException("{$type}filter \"{$name}\" not callable");
        return false;
    }


}
?>