<?php

/**
* Smarty Internal Plugin Debug
*
* Class to collect data for the Smarty Debugging Consol
*
* @package Smarty
* @subpackage Debug
* @author Uwe Tews
*/

/**
* Smarty Internal Plugin Debug Class
*/
class Smarty_Internal_Debug extends Smarty_Internal_Data {
	// template data
	static $template_data = array();

	/**
	* Start logging of compile time
	*/
	public static function start_compile($template)
	{
		$key = self::get_key($template);
		self::$template_data[$key]['start_time'] = microtime(true);
	}

	/**
	* End logging of compile time
	*/
	public static function end_compile($template)
	{
		$key = self::get_key($template);
		self::$template_data[$key]['compile_time'] += microtime(true) - self::$template_data[$key]['start_time'];
	}

	/**
	* Start logging of render time
	*/
	public static function start_render($template)
	{
		$key = self::get_key($template);
		self::$template_data[$key]['start_time'] = microtime(true);
	}

	/**
	* End logging of compile time
	*/
	public static function end_render($template)
	{
		$key = self::get_key($template);
		self::$template_data[$key]['render_time'] += microtime(true) - self::$template_data[$key]['start_time'];
	}

	/**
	* Start logging of cache time
	*/
	public static function start_cache($template)
	{
		$key = self::get_key($template);
		self::$template_data[$key]['start_time'] = microtime(true);
	}

	/**
	* End logging of cache time
	*/
	public static function end_cache($template)
	{
		$key = self::get_key($template);
		self::$template_data[$key]['cache_time'] += microtime(true) - self::$template_data[$key]['start_time'];
	}
	/**
	* Opens a window for the Smarty Debugging Consol and display the data
	*/
	public static function display_debug($obj)
	{
		// prepare information of assigned variables
		$ptr = self::get_debug_vars($obj);
		if ($obj instanceof Smarty) {
			$smarty = $obj;
		} else {
			$smarty = $obj->smarty;
		}
		$_assigned_vars = $ptr->tpl_vars;
		ksort($_assigned_vars);
		$_config_vars = $ptr->config_vars;
		ksort($_config_vars);
		$ldelim = $smarty->left_delimiter;
		$rdelim = $smarty->right_delimiter;
		$smarty->left_delimiter = '{';
		$smarty->right_delimiter = '}';
		$_template = new Smarty_Internal_Template ($smarty->debug_tpl, $smarty);
		$_template->caching = false;
		$_template->force_compile = false;
		$_template->disableSecurity();
		$_template->cache_id = null;
		$_template->compile_id = null;
		if ($obj instanceof Smarty_Internal_Template) {
			$_template->assign('template_name',$obj->resource_type.':'.$obj->resource_name);
		}
		if ($obj instanceof Smarty) {
			$_template->assign('template_data', self::$template_data);
		} else {
			$_template->assign('template_data', null);
		}
		$_template->assign('assigned_vars', $_assigned_vars);
		$_template->assign('config_vars', $_config_vars);
		$_template->assign('execution_time', microtime(true) - $smarty->start_time);
		echo $_template->getRenderedTemplate();
		$smarty->left_delimiter = $ldelim;
		$smarty->right_delimiter = $rdelim;
	}
	/*
	* Recursively gets variables from all template/data scopes
	*/
	public static function get_debug_vars($obj)
	{
		$config_vars = $obj->config_vars;
		$tpl_vars = array();
		foreach ($obj->tpl_vars as $key => $var) {
			$tpl_vars[$key] = clone $var;
			if ($obj instanceof Smarty_Internal_Template) {
				$tpl_vars[$key]->scope = $obj->resource_type.':'.$obj->resource_name;
			} elseif ($obj instanceof Smarty_Data) {
				$tpl_vars[$key]->scope = 'Data object';
			} else {
				$tpl_vars[$key]->scope = 'Smarty root';
			}
		}

		if (isset($obj->parent)) {
			$parent = self::get_debug_vars($obj->parent);
			$tpl_vars = array_merge($parent->tpl_vars, $tpl_vars);
			$config_vars = array_merge($parent->config_vars, $config_vars);
		} else {
			foreach (Smarty::$global_tpl_vars as $name => $var) {
				if (!array_key_exists($name, $tpl_vars)) {
					$clone = clone $var;
					$clone->scope = 'Global';
					$tpl_vars[$name] = $clone;
				}
			}
		}
		return (object) array('tpl_vars' => $tpl_vars, 'config_vars' => $config_vars);
	}

	/**
	* get_key
	*/
	static function get_key($template)
	{
		// calculate Uid if not already done
		if ($template->templateUid == '') {
			$template->getTemplateFilepath();
		}
		$key = $template->templateUid;
		if (isset(self::$template_data[$key])) {
			return $key;
		} else {
			self::$template_data[$key]['name'] = $template->getTemplateFilepath();
			self::$template_data[$key]['compile_time'] = 0;
			self::$template_data[$key]['render_time'] = 0;
			self::$template_data[$key]['cache_time'] = 0;
			return $key;
		}
	}
}

?>