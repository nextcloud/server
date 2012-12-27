<?php
/**
 * Bootstrapping File for phpseclib Test Suite
 *
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

// Set up include path accordingly. This is especially required because some
// class files of phpseclib require() other dependencies.
set_include_path(implode(PATH_SEPARATOR, array(
	dirname(__FILE__) . '/../phpseclib/',
	get_include_path(),
)));

function phpseclib_autoload($class)
{
	$file = str_replace('_', '/', $class) . '.php';

	require $file;
}

spl_autoload_register('phpseclib_autoload');
