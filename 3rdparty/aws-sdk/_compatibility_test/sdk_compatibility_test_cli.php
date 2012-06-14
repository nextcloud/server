#! /usr/bin/env php
<?php

//Prevent script from being called via browser
if (PHP_SAPI !== 'cli')
{
	die('ERROR: You may only run the compatibility test from the command line.');
}

// Include the compatibility test logic
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sdk_compatibility.inc.php';

// CLI display
function success($s = 'Yes')
{
	return is_windows() ? $s : "\033[1;37m\033[42m " . $s . " \033[0m";
}

function info($s = 'Info')
{
	return is_windows() ? $s : "\033[1;37m\033[44m " . $s . " \033[0m";
}

function failure($s = 'No ')
{
	return is_windows() ? $s : "\033[1;37m\033[41m " . $s . " \033[0m";
}

/////////////////////////////////////////////////////////////////////////

echo PHP_EOL;

echo info('AWS SDK for PHP') . PHP_EOL;
echo 'PHP Environment Compatibility Test (CLI)' . PHP_EOL;
echo '----------------------------------------' . PHP_EOL;
echo PHP_EOL;

echo 'PHP 5.2 or newer............ ' . ($php_ok ? (success() . ' ' . phpversion()) : failure()) . PHP_EOL;
echo '64-bit architecture......... ' . ($int64_ok ? success() : failure()) . (is_windows() ? ' (see note below)' : '') . PHP_EOL;
echo 'cURL with SSL............... ' . ($curl_ok ? (success() . ' ' . $curl_version['version'] . ' (' . $curl_version['ssl_version'] . ')') : failure($curl_version['version'] . (in_array('https', $curl_version['protocols'], true) ? ' (with ' . $curl_version['ssl_version'] . ')' : ' (without SSL)'))) . PHP_EOL;
echo 'Standard PHP Library........ ' . ($spl_ok ? success() : failure()) . PHP_EOL;
echo 'SimpleXML................... ' . ($simplexml_ok ? success() : failure()) . PHP_EOL;
echo 'DOM......................... ' . ($dom_ok ? success() : failure()) . PHP_EOL;
echo 'JSON........................ ' . ($json_ok ? success() : failure()) . PHP_EOL;
echo 'PCRE........................ ' . ($pcre_ok ? success() : failure()) . PHP_EOL;
echo 'File system read/write...... ' . ($file_ok ? success() : failure()) . PHP_EOL;
echo 'OpenSSL extension........... ' . ($openssl_ok ? success() : failure()) . PHP_EOL;
echo 'Zlib........................ ' . ($zlib_ok ? success() : failure()) . PHP_EOL;
echo 'APC......................... ' . ($apc_ok ? success() : failure()) . PHP_EOL;
echo 'XCache...................... ' . ($xcache_ok ? success() : failure()) . PHP_EOL;
echo 'Memcache.................... ' . ($memcache_ok ? success() : failure()) . PHP_EOL;
echo 'Memcached................... ' . ($memcached_ok ? success() : failure()) . PHP_EOL;
echo 'PDO......................... ' . ($pdo_ok ? success() : failure()) . PHP_EOL;
echo 'SQLite 2.................... ' . ($sqlite2_ok ? success() : failure()) . PHP_EOL;
echo 'SQLite 3.................... ' . ($sqlite3_ok ? success() : failure()) . PHP_EOL;
echo 'PDO-SQLite driver........... ' . ($pdo_sqlite_ok ? success() : failure()) . PHP_EOL;
echo 'open_basedir disabled....... ' . (!$ini_open_basedir ? success() : failure()) . PHP_EOL;
echo 'safe_mode disabled.......... ' . (!$ini_safe_mode ? success() : failure()) . PHP_EOL;
echo 'Garbage Collector enabled... ' . ($ini_zend_enable_gc ? success() : failure()) . PHP_EOL;

// Test SSL cert
if (!is_windows())
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://email.us-east-1.amazonaws.com');
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5184000);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
	curl_setopt($ch, CURLOPT_NOSIGNAL, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'aws-sdk-php/compat-cli');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	curl_exec($ch);
	$ssl_result = !(curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT) === 0);
	curl_close($ch);

	echo 'Valid SSL certificate....... ' . ($ssl_result ? failure() : success()) . PHP_EOL;
}
else
{
	$ssl_result = false;
	echo 'Valid SSL certificate....... ' . failure() . ' (will use the bundled certificate instead)' . PHP_EOL;
}

echo PHP_EOL;

echo '----------------------------------------' . PHP_EOL;
echo PHP_EOL;

if ($compatiblity >= REQUIREMENTS_MIN_MET)
{
	echo success('Your environment meets the minimum requirements for using the AWS SDK for PHP!') . PHP_EOL . PHP_EOL;
	if (version_compare(PHP_VERSION, '5.3.0') < 0) { echo '* You\'re still running PHP ' . PHP_VERSION . '. The PHP 5.2 family is no longer supported' . PHP_EOL . '  by the PHP team, and future versions of the AWS SDK for PHP will *require*' . PHP_EOL . '  PHP 5.3 or newer.' . PHP_EOL . PHP_EOL; }
	if ($openssl_ok) { echo '* The OpenSSL extension is installed. This will allow you to use CloudFront' . PHP_EOL . '  Private URLs and decrypt Windows instance passwords.' . PHP_EOL . PHP_EOL; }
	if ($zlib_ok) {    echo '* The Zlib extension is installed. The SDK will request gzipped data' . PHP_EOL . '  whenever possible.' . PHP_EOL . PHP_EOL; }
	if (!$int64_ok) {  echo '* You\'re running on a 32-bit system. This means that PHP does not correctly' . PHP_EOL . '  handle files larger than 2GB (this is a well-known PHP issue).' . PHP_EOL . PHP_EOL; }
	if (!$int64_ok && is_windows()) {  echo '* Note that PHP on Microsoft(R) Windows(R) does not support 64-bit integers' . PHP_EOL . '  at all, even if both the hardware and PHP are 64-bit. http://j.mp/php64win' . PHP_EOL . PHP_EOL; }

	if ($ini_open_basedir || $ini_safe_mode) { echo '* You have open_basedir or safe_mode enabled in your php.ini file. Sometimes' . PHP_EOL . '  PHP behaves strangely when these settings are enabled. Disable them if you can.' . PHP_EOL . PHP_EOL; }
	if (!$ini_zend_enable_gc) { echo '* The PHP garbage collector (available in PHP 5.3+) is not enabled in your' . PHP_EOL . '  php.ini file. Enabling zend.enable_gc will provide better memory management' . PHP_EOL . '  in the PHP core.' . PHP_EOL . PHP_EOL; }

	$storage_types = array();
	if ($file_ok) { $storage_types[] = 'The file system'; }
	if ($apc_ok) { $storage_types[] = 'APC'; }
	if ($xcache_ok) { $storage_types[] = 'XCache'; }
	if ($sqlite_ok && $sqlite3_ok) { $storage_types[] = 'SQLite 3'; }
	elseif ($sqlite_ok && $sqlite2_ok) { $storage_types[] = 'SQLite 2'; }
	if ($memcached_ok) { $storage_types[] = 'Memcached'; }
	elseif ($memcache_ok) { $storage_types[] = 'Memcache'; }
	echo '* Storage types available for response caching:' . PHP_EOL . '  ' . implode(', ', $storage_types) . PHP_EOL . PHP_EOL;

	if (!$openssl_ok) { echo '* You\'re missing the OpenSSL extension, which means that you won\'t be able' . PHP_EOL . '  to take advantage of CloudFront Private URLs or Windows password decryption.' . PHP_EOL . PHP_EOL; }
	if (!$zlib_ok) {    echo '* You\'re missing the Zlib extension, which means that the SDK will be unable' . PHP_EOL . '  to request gzipped data from Amazon and you won\'t be able to take advantage' . PHP_EOL . '  of compression with the response caching feature.' . PHP_EOL . PHP_EOL; }
}
else
{
	if (!$php_ok) {       echo '* ' . failure('PHP:') . ' You are running an unsupported version of PHP.' . PHP_EOL . PHP_EOL; }
	if (!$curl_ok) {      echo '* ' . failure('cURL:') . ' The cURL extension is not available. Without cURL, the SDK cannot' . PHP_EOL . '  connect to -- or authenticate with -- Amazon\'s services.' . PHP_EOL . PHP_EOL; }
	if (!$simplexml_ok) { echo '* ' . failure('SimpleXML:') . ': The SimpleXML extension is not available. Without SimpleXML,' . PHP_EOL . '  the SDK cannot parse the XML responses from Amazon\'s services.' . PHP_EOL . PHP_EOL; }
	if (!$dom_ok) {       echo '* ' . failure('DOM:') . ': The DOM extension is not available. Without DOM, the SDK' . PHP_EOL . '  Without DOM, the SDK cannot transliterate JSON responses from Amazon\'s' . PHP_EOL . '  services into the common SimpleXML-based pattern used throughout the SDK.' . PHP_EOL . PHP_EOL; }
	if (!$spl_ok) {       echo '* ' . failure('SPL:') . ' Standard PHP Library support is not available. Without SPL support,' . PHP_EOL . '  the SDK cannot autoload the required PHP classes.' . PHP_EOL . PHP_EOL; }
	if (!$json_ok) {      echo '* ' . failure('JSON:') . ' JSON support is not available. AWS leverages JSON heavily in many' . PHP_EOL . '  of its services.' . PHP_EOL . PHP_EOL; }
	if (!$pcre_ok) {      echo '* ' . failure('PCRE:') . ' Your PHP installation doesn\'t support Perl-Compatible Regular' . PHP_EOL . '  Expressions (PCRE). Without PCRE, the SDK cannot do any filtering via' . PHP_EOL . '  regular expressions.' . PHP_EOL . PHP_EOL; }
	if (!$file_ok) {      echo '* ' . failure('File System Read/Write:') . ' The file_get_contents() and/or file_put_contents()' . PHP_EOL . '  functions have been disabled. Without them, the SDK cannot read from,' . PHP_EOL . '  or write to, the file system.' . PHP_EOL . PHP_EOL; }
}

echo '----------------------------------------' . PHP_EOL;
echo PHP_EOL;

if ($compatiblity === REQUIREMENTS_ALL_MET)
{
	echo success('Bottom Line: Yes, you can!') . PHP_EOL;
	echo PHP_EOL;
	echo 'Your PHP environment is ready to go, and can take advantage of all possible features!' . PHP_EOL;

	echo PHP_EOL;
	echo info('Recommended settings for config.inc.php') . PHP_EOL;
	echo PHP_EOL;

	echo "CFCredentials::set(array(" . PHP_EOL;
	echo "    '@default' => array(" . PHP_EOL;
	echo "        'key' => 'aws-key'," . PHP_EOL;
	echo "        'secret' => 'aws-secret'," . PHP_EOL;
	echo "        'default_cache_config' => ";
	if ($apc_ok) echo success('\'apc\'');
	elseif ($xcache_ok) echo success('\'xcache\'');
	elseif ($file_ok) echo success('\'/path/to/cache/folder\'');
	echo "," . PHP_EOL;
	echo "        'certificate_authority' => " . success($ssl_result ? 'true' : 'false') . PHP_EOL;
	echo "    )" . PHP_EOL;
	echo "));" . PHP_EOL;
}
elseif ($compatiblity === REQUIREMENTS_MIN_MET)
{
	echo success('Bottom Line: Yes, you can!') . PHP_EOL;
	echo PHP_EOL;
	echo 'Your PHP environment is ready to go! There are a couple of minor features that' . PHP_EOL . 'you won\'t be able to take advantage of, but nothing that\'s a show-stopper.' . PHP_EOL;

	echo PHP_EOL;
	echo info('Recommended settings for config.inc.php') . PHP_EOL;
	echo PHP_EOL;

	echo "CFCredentials::set(array(" . PHP_EOL;
	echo "    '@default' => array(" . PHP_EOL;
	echo "        'key' => 'aws-key'," . PHP_EOL;
	echo "        'secret' => 'aws-secret'," . PHP_EOL;
	echo "        'default_cache_config' => ";
	if ($apc_ok) echo success('\'apc\'');
	elseif ($xcache_ok) echo success('\'xcache\'');
	elseif ($file_ok) echo success('\'/path/to/cache/folder\'');
	echo "," . PHP_EOL;
	echo "        'certificate_authority' => " . ($ssl_result ? 'false' : 'true') . PHP_EOL;
	echo "    )" . PHP_EOL;
	echo "));" . PHP_EOL;
}
else
{
	echo failure('Bottom Line: We\'re sorry...') . PHP_EOL;
	echo 'Your PHP environment does not support the minimum requirements for the ' . PHP_EOL . 'AWS SDK for PHP.' . PHP_EOL;
}

echo PHP_EOL;
