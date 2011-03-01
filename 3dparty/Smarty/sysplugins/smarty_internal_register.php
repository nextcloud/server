<?php

/**
 * Smarty Internal Plugin Register
 *
 * External Smarty methods register/unregister
 *
 * @package Smarty
 * @author Uwe Tews
 */

/**
 * Class for register/unregister methods
 */
class Smarty_Internal_Register {

    function __construct($smarty)
    {
        $this->smarty = $smarty;
    }
    /**
     * Registers plugin to be used in templates
     *
     * @param string $type plugin type
     * @param string $tag name of template tag
     * @param callback $callback PHP callback to register
     * @param boolean $cacheable if true (default) this fuction is cachable
     * @param array $cache_attr caching attributes if any
     */

	public function registerPlugin($type, $tag, $callback, $cacheable = true, $cache_attr = null)
	{
		if (isset($this->smarty->registered_plugins[$type][$tag])) {
        	throw new Exception("Plugin tag \"{$tag}\" already registered");
    	} elseif (!is_callable($callback)) {
        	throw new Exception("Plugin \"{$tag}\" not callable");
    	} else {
       		$this->smarty->registered_plugins[$type][$tag] = array($callback, (bool) $cacheable, (array) $cache_attr);
    	}
	}

    /**
     * Unregister Plugin
     *
     * @param string $type of plugin
     * @param string $tag name of plugin
     */
    function unregisterPlugin($type, $tag)
    {
        if (isset($this->smarty->registered_plugins[$type][$tag])) {
            unset($this->smarty->registered_plugins[$type][$tag]);
        }
    }

    /**
     * Registers a resource to fetch a template
     *
     * @param string $type name of resource type
     * @param array $callback array of callbacks to handle resource
     */
 	public function registerResource($type, $callback)
	{
       	$this->smarty->registered_resources[$type] = array($callback, false);
    }

    /**
     * Unregisters a resource
     *
     * @param string $type name of resource type
     */
   function unregisterResource($type)
    {
        if (isset($this->smarty->registered_resources[$type])) {
            unset($this->smarty->registered_resources[$type]);
        }
    }


    /**
     * Registers object to be used in templates
     *
     * @param string $object name of template object
     * @param object $ &$object_impl the referenced PHP object to register
     * @param mixed $ null | array $allowed list of allowed methods (empty = all)
     * @param boolean $smarty_args smarty argument format, else traditional
     * @param mixed $ null | array $block_functs list of methods that are block format
     */
    function registerObject($object_name, $object_impl, $allowed = array(), $smarty_args = true, $block_methods = array())
    {
        // test if allowed methodes callable
        if (!empty($allowed)) {
            foreach ((array)$allowed as $method) {
                if (!is_callable(array($object_impl, $method))) {
                    throw new SmartyException("Undefined method '$method' in registered object");
                }
            }
        }
        // test if block methodes callable
        if (!empty($block_methods)) {
            foreach ((array)$block_methods as $method) {
                if (!is_callable(array($object_impl, $method))) {
                    throw new SmartyException("Undefined method '$method' in registered object");
                }
            }
        }
        // register the object
        $this->smarty->registered_objects[$object_name] =
        array($object_impl, (array)$allowed, (boolean)$smarty_args, (array)$block_methods);
    }

    /**
     * Registers static classes to be used in templates
     *
     * @param string $class name of template class
     * @param string $class_impl the referenced PHP class to register
     */
    function registerClass($class_name, $class_impl)
    {
        // test if exists
        if (!class_exists($class_impl)) {
            throw new SmartyException("Undefined class '$class_impl' in register template class");
        }
        // register the class
        $this->smarty->registered_classes[$class_name] = $class_impl;
    }

    /**
     * Registers a default plugin handler
     *
     * @param  $callback mixed string | array $plugin class/methode name
     */
    function registerDefaultPluginHandler($callback)
    {
        if (is_callable($callback)) {
            $this->smarty->default_plugin_handler_func = $callback;
        } else {
            throw new SmartyException("Default plugin handler '$callback' not callable");
        }
    }

    /**
     * Registers a default template handler
     *
     * @param  $callback mixed string | array class/method name
     */
    function registerDefaultTemplateHandler($callback)
    {
        if (is_callable($callback)) {
            $this->smarty->default_template_handler_func = $callback;
        } else {
            throw new SmartyException("Default template handler '$callback' not callable");
        }
    }

}
?>