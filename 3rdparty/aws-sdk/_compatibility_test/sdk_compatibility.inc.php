<?php

define('REQUIREMENTS_ALL_MET', 100);
define('REQUIREMENTS_MIN_MET', 10);
define('REQUIREMENTS_NOT_MET', 0);

// Required
$php_ok = (function_exists('version_compare') && version_compare(phpversion(), '5.2.0', '>='));
$simplexml_ok = extension_loaded('simplexml');
$dom_ok = extension_loaded('dom');
$json_ok = (extension_loaded('json') && function_exists('json_encode') && function_exists('json_decode'));
$spl_ok = extension_loaded('spl');
$pcre_ok = extension_loaded('pcre');
$curl_ok = false;
if (function_exists('curl_version'))
{
	$curl_version = curl_version();
	$curl_ok = (function_exists('curl_exec') && in_array('https', $curl_version['protocols'], true));
}
$file_ok = (function_exists('file_get_contents') && function_exists('file_put_contents'));

// Optional, but recommended
$openssl_ok = (extension_loaded('openssl') && function_exists('openssl_sign'));
$zlib_ok = extension_loaded('zlib');

// Optional
$apc_ok = extension_loaded('apc');
$xcache_ok = extension_loaded('xcache');
$memcached_ok = extension_loaded('memcached');
$memcache_ok = extension_loaded('memcache');
$mc_ok = ($memcache_ok || $memcached_ok);
$pdo_ok = extension_loaded('pdo');
$pdo_sqlite_ok = extension_loaded('pdo_sqlite');
$sqlite2_ok = extension_loaded('sqlite');
$sqlite3_ok = extension_loaded('sqlite3');
$sqlite_ok = ($pdo_ok && $pdo_sqlite_ok && ($sqlite2_ok || $sqlite3_ok));

// Other
$int64_ok = (PHP_INT_MAX === 9223372036854775807);
$ini_memory_limit = get_ini('memory_limit');
$ini_open_basedir = get_ini('open_basedir');
$ini_safe_mode = get_ini('safe_mode');
$ini_zend_enable_gc = get_ini('zend.enable_gc');

if ($php_ok && $int64_ok && $curl_ok && $simplexml_ok && $dom_ok && $spl_ok && $json_ok && $pcre_ok && $file_ok && $openssl_ok && $zlib_ok && ($apc_ok || $xcache_ok || $mc_ok || $sqlite_ok))
{
	$compatiblity = REQUIREMENTS_ALL_MET;
}
elseif ($php_ok && $curl_ok && $simplexml_ok && $dom_ok && $spl_ok && $json_ok && $pcre_ok && $file_ok)
{
	$compatiblity = REQUIREMENTS_MIN_MET;
}
else
{
	$compatiblity = REQUIREMENTS_NOT_MET;
}

function get_ini($config)
{
	$cfg_value = ini_get($config);

	if ($cfg_value === false || $cfg_value === '' || $cfg_value === 0)
	{
		return false;
	}
	elseif ($cfg_value === true || $cfg_value === '1' || $cfg_value === 1)
	{
		return true;
	}
}

function is_windows()
{
	return strtolower(substr(PHP_OS, 0, 3)) === 'win';
}
