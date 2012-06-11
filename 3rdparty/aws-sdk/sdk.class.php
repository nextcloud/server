<?php
/*
 * Copyright 2010-2012 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */


/*%******************************************************************************************%*/
// EXCEPTIONS

/**
 * Default CFRuntime Exception.
 */
class CFRuntime_Exception extends Exception {}

/**
 * Parsing Exception.
 */
class Parser_Exception extends Exception {}


/*%******************************************************************************************%*/
// DETERMINE WHAT ENVIRONMENT DATA TO ADD TO THE USERAGENT FOR METRIC TRACKING

/*
	Define a temporary callback function for this calculation. Get the PHP version and any
	required/optional extensions that are leveraged.

	Tracking this data gives Amazon better metrics about what configurations are being used
	so that forward-looking plans for the code can be made with more certainty (e.g. What
	version of PHP are most people running? Do they tend to have the latest PCRE?).
*/
function __aws_sdk_ua_callback()
{
	$ua_append = '';
	$extensions = get_loaded_extensions();
	$sorted_extensions = array();

	if ($extensions)
	{
		foreach ($extensions as $extension)
		{
			if ($extension === 'curl' && function_exists('curl_version'))
			{
				$curl_version = curl_version();
				$sorted_extensions[strtolower($extension)] = $curl_version['version'];
			}
			elseif ($extension === 'pcre' && defined('PCRE_VERSION'))
			{
				$pcre_version = explode(' ', PCRE_VERSION);
				$sorted_extensions[strtolower($extension)] = $pcre_version[0];
			}
			elseif ($extension === 'openssl' && defined('OPENSSL_VERSION_TEXT'))
			{
				$openssl_version = explode(' ', OPENSSL_VERSION_TEXT);
				$sorted_extensions[strtolower($extension)] = $openssl_version[1];
			}
			else
			{
				$sorted_extensions[strtolower($extension)] = phpversion($extension);
			}
		}
	}

	foreach (array('simplexml', 'json', 'pcre', 'spl', 'curl', 'openssl', 'apc', 'xcache', 'memcache', 'memcached', 'pdo', 'pdo_sqlite', 'sqlite', 'sqlite3', 'zlib', 'xdebug') as $ua_ext)
	{
		if (isset($sorted_extensions[$ua_ext]) && $sorted_extensions[$ua_ext])
		{
			$ua_append .= ' ' . $ua_ext . '/' . $sorted_extensions[$ua_ext];
		}
		elseif (isset($sorted_extensions[$ua_ext]))
		{
			$ua_append .= ' ' . $ua_ext . '/0';
		}
	}

	foreach (array('memory_limit', 'date.timezone', 'open_basedir', 'safe_mode', 'zend.enable_gc') as $cfg)
	{
		$cfg_value = ini_get($cfg);

		if (in_array($cfg, array('memory_limit', 'date.timezone'), true))
		{
			$ua_append .= ' ' . $cfg . '/' . str_replace('/', '.', $cfg_value);
		}
		elseif (in_array($cfg, array('open_basedir', 'safe_mode', 'zend.enable_gc'), true))
		{
			if ($cfg_value === false || $cfg_value === '' || $cfg_value === 0)
			{
				$cfg_value = 'off';
			}
			elseif ($cfg_value === true || $cfg_value === '1' || $cfg_value === 1)
			{
				$cfg_value = 'on';
			}

			$ua_append .= ' ' . $cfg . '/' . $cfg_value;
		}
	}

	return $ua_append;
}


/*%******************************************************************************************%*/
// INTERMEDIARY CONSTANTS

define('CFRUNTIME_NAME', 'aws-sdk-php');
define('CFRUNTIME_VERSION', '1.5.6.2');
define('CFRUNTIME_BUILD', '20120529180000');
define('CFRUNTIME_USERAGENT', CFRUNTIME_NAME . '/' . CFRUNTIME_VERSION . ' PHP/' . PHP_VERSION . ' ' . str_replace(' ', '_', php_uname('s')) . '/' . str_replace(' ', '_', php_uname('r')) . ' Arch/' . php_uname('m') . ' SAPI/' . php_sapi_name() . ' Integer/' . PHP_INT_MAX . ' Build/' . CFRUNTIME_BUILD . __aws_sdk_ua_callback());


/*%******************************************************************************************%*/
// CLASS

/**
 * Core functionality and default settings shared across all SDK classes. All methods and properties in this
 * class are inherited by the service-specific classes.
 *
 * @version 2012.05.29
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFRuntime
{
	/*%******************************************************************************************%*/
	// CONSTANTS

	/**
	 * Name of the software.
	 */
	const NAME = CFRUNTIME_NAME;

	/**
	 * Version of the software.
	 */
	const VERSION = CFRUNTIME_VERSION;

	/**
	 * Build ID of the software.
	 */
	const BUILD = CFRUNTIME_BUILD;

	/**
	 * User agent string used to identify the software.
	 */
	const USERAGENT = CFRUNTIME_USERAGENT;


	/*%******************************************************************************************%*/
	// PROPERTIES

	/**
	 * The Amazon API Key.
	 */
	public $key;

	/**
	 * The Amazon API Secret Key.
	 */
	public $secret_key;

	/**
	 * The Amazon Authentication Token.
	 */
	public $auth_token;

	/**
	 * Handle for the utility functions.
	 */
	public $util;

	/**
	 * An identifier for the current AWS service.
	 */
	public $service = null;

	/**
	 * The supported API version.
	 */
	public $api_version = null;

	/**
	 * The state of whether auth should be handled as AWS Query.
	 */
	public $use_aws_query = true;

	/**
	 * The default class to use for utilities (defaults to <CFUtilities>).
	 */
	public $utilities_class = 'CFUtilities';

	/**
	 * The default class to use for HTTP requests (defaults to <CFRequest>).
	 */
	public $request_class = 'CFRequest';

	/**
	 * The default class to use for HTTP responses (defaults to <CFResponse>).
	 */
	public $response_class = 'CFResponse';

	/**
	 * The default class to use for parsing XML (defaults to <CFSimpleXML>).
	 */
	public $parser_class = 'CFSimpleXML';

	/**
	 * The default class to use for handling batch requests (defaults to <CFBatchRequest>).
	 */
	public $batch_class = 'CFBatchRequest';

	/**
	 * The state of SSL/HTTPS use.
	 */
	public $use_ssl = true;

	/**
	 * The state of SSL certificate verification.
	 */
	public $ssl_verification = true;

	/**
	 * The proxy to use for connecting.
	 */
	public $proxy = null;

	/**
	 * The alternate hostname to use, if any.
	 */
	public $hostname = null;

	/**
	 * The state of the capability to override the hostname with <set_hostname()>.
	 */
	public $override_hostname = true;

	/**
	 * The alternate port number to use, if any.
	 */
	public $port_number = null;

	/**
	 * The alternate resource prefix to use, if any.
	 */
	public $resource_prefix = null;

	/**
	 * The state of cache flow usage.
	 */
	public $use_cache_flow = false;

	/**
	 * The caching class to use.
	 */
	public $cache_class = null;

	/**
	 * The caching location to use.
	 */
	public $cache_location = null;

	/**
	 * When the cache should be considered stale.
	 */
	public $cache_expires = null;

	/**
	 * The state of cache compression.
	 */
	public $cache_compress = null;

	/**
	 * The current instantiated cache object.
	 */
	public $cache_object = null;

	/**
	 * The current instantiated batch request object.
	 */
	public $batch_object = null;

	/**
	 * The internally instantiated batch request object.
	 */
	public $internal_batch_object = null;

	/**
	 * The state of batch flow usage.
	 */
	public $use_batch_flow = false;

	/**
	 * The state of the cache deletion setting.
	 */
	public $delete_cache = false;

	/**
	 * The state of the debug mode setting.
	 */
	public $debug_mode = false;

	/**
	 * The number of times to retry failed requests.
	 */
	public $max_retries = 3;

	/**
	 * The user-defined callback function to call when a stream is read from.
	 */
	public $registered_streaming_read_callback = null;

	/**
	 * The user-defined callback function to call when a stream is written to.
	 */
	public $registered_streaming_write_callback = null;

	/**
	 * The credentials to use for authentication.
	 */
	public $credentials = array();

	/**
	 * The authentication class to use.
	 */
	public $auth_class = null;

	/**
	 * The operation to execute.
	 */
	public $operation = null;

	/**
	 * The payload to send.
	 */
	public $payload = array();

	/**
	 * The string prefix to prepend to the operation name.
	 */
	public $operation_prefix = '';

	/**
	 * The number of times a request has been retried.
	 */
	public $redirects = 0;

	/**
	 * The state of whether the response should be parsed or not.
	 */
	public $parse_the_response = true;


	/*%******************************************************************************************%*/
	// CONSTRUCTOR

	/**
	 * The constructor. This class should not be instantiated directly. Rather, a service-specific class
	 * should be instantiated.
	 *
	 * @param array $options (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>certificate_authority</code> - <code>boolean</code> - Optional - Determines which Cerificate Authority file to use. A value of boolean <code>false</code> will use the Certificate Authority file available on the system. A value of boolean <code>true</code> will use the Certificate Authority provided by the SDK. Passing a file system path to a Certificate Authority file (chmodded to <code>0755</code>) will use that. Leave this set to <code>false</code> if you're not sure.</li>
	 * 	<li><code>credentials</code> - <code>string</code> - Optional - The name of the credential set to use for authentication.</li>
	 * 	<li><code>default_cache_config</code> - <code>string</code> - Optional - This option allows a preferred storage type to be configured for long-term caching. This can be changed later using the <set_cache_config()> method. Valid values are: <code>apc</code>, <code>xcache</code>, or a file system path such as <code>./cache</code> or <code>/tmp/cache/</code>.</li>
	 * 	<li><code>key</code> - <code>string</code> - Optional - Your AWS key, or a session key. If blank, the default credential set will be used.</li>
	 * 	<li><code>secret</code> - <code>string</code> - Optional - Your AWS secret key, or a session secret key. If blank, the default credential set will be used.</li>
	 * 	<li><code>token</code> - <code>string</code> - Optional - An AWS session token.</li></ul>
	 * @return void
	 */
	public function __construct(array $options = array())
	{
		// Instantiate the utilities class.
		$this->util = new $this->utilities_class();

		// Determine the current service.
		$this->service = get_class($this);

		// Create credentials based on the options
		$instance_credentials = new CFCredential($options);

		// Retreive a credential set from config.inc.php if it exists
		if (isset($options['credentials']))
		{
			// Use a specific credential set and merge with the instance credentials
			$this->credentials = CFCredentials::get($options['credentials'])
				->merge($instance_credentials);
		}
		else
		{
			try
			{
				// Use the default credential set and merge with the instance credentials
				$this->credentials = CFCredentials::get(CFCredentials::DEFAULT_KEY)
					->merge($instance_credentials);
			}
			catch (CFCredentials_Exception $e)
			{
				if (isset($options['key']) && isset($options['secret']))
				{
					// Only the instance credentials were provided
					$this->credentials = $instance_credentials;
				}
				else
				{
					// No credentials provided in the config file or constructor
					throw new CFCredentials_Exception('No credentials were provided to ' . $this->service . '.');
				}
			}
		}

		// Set internal credentials after they are resolved
		$this->key = $this->credentials->key;
		$this->secret_key = $this->credentials->secret;
		$this->auth_token = $this->credentials->token;

		// Automatically enable whichever caching mechanism is set to default.
		$this->set_cache_config($this->credentials->default_cache_config);
	}

	/**
	 * Alternate approach to constructing a new instance. Supports chaining.
	 *
	 * @param array $options (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>certificate_authority</code> - <code>boolean</code> - Optional - Determines which Cerificate Authority file to use. A value of boolean <code>false</code> will use the Certificate Authority file available on the system. A value of boolean <code>true</code> will use the Certificate Authority provided by the SDK. Passing a file system path to a Certificate Authority file (chmodded to <code>0755</code>) will use that. Leave this set to <code>false</code> if you're not sure.</li>
	 * 	<li><code>credentials</code> - <code>string</code> - Optional - The name of the credential set to use for authentication.</li>
	 * 	<li><code>default_cache_config</code> - <code>string</code> - Optional - This option allows a preferred storage type to be configured for long-term caching. This can be changed later using the <set_cache_config()> method. Valid values are: <code>apc</code>, <code>xcache</code>, or a file system path such as <code>./cache</code> or <code>/tmp/cache/</code>.</li>
	 * 	<li><code>key</code> - <code>string</code> - Optional - Your AWS key, or a session key. If blank, the default credential set will be used.</li>
	 * 	<li><code>secret</code> - <code>string</code> - Optional - Your AWS secret key, or a session secret key. If blank, the default credential set will be used.</li>
	 * 	<li><code>token</code> - <code>string</code> - Optional - An AWS session token.</li></ul>
	 * @return void
	 */
	public static function factory(array $options = array())
	{
		if (version_compare(PHP_VERSION, '5.3.0', '<'))
		{
			throw new Exception('PHP 5.3 or newer is required to instantiate a new class with CLASS::factory().');
		}

		$self = get_called_class();
		return new $self($options);
	}


	/*%******************************************************************************************%*/
	// MAGIC METHODS

	/**
	 * A magic method that allows `camelCase` method names to be translated into `snake_case` names.
	 *
	 * @param string $name (Required) The name of the method.
	 * @param array $arguments (Required) The arguments passed to the method.
	 * @return mixed The results of the intended method.
	 */
	public function  __call($name, $arguments)
	{
		// Convert camelCase method calls to snake_case.
		$method_name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));

		if (method_exists($this, $method_name))
		{
			return call_user_func_array(array($this, $method_name), $arguments);
		}

		throw new CFRuntime_Exception('The method ' . $name . '() is undefined. Attempted to map to ' . $method_name . '() which is also undefined. Error occurred');
	}


	/*%******************************************************************************************%*/
	// SET CUSTOM SETTINGS

	/**
	 * Set the proxy settings to use.
	 *
	 * @param string $proxy (Required) Accepts proxy credentials in the following format: `proxy://user:pass@hostname:port`
	 * @return $this A reference to the current instance.
	 */
	public function set_proxy($proxy)
	{
		$this->proxy = $proxy;
		return $this;
	}

	/**
	 * Set the hostname to connect to. This is useful for alternate services that are API-compatible with
	 * AWS, but run from a different hostname.
	 *
	 * @param string $hostname (Required) The alternate hostname to use in place of the default one. Useful for mock or test applications living on different hostnames.
	 * @param integer $port_number (Optional) The alternate port number to use in place of the default one. Useful for mock or test applications living on different port numbers.
	 * @return $this A reference to the current instance.
	 */
	public function set_hostname($hostname, $port_number = null)
	{
		if ($this->override_hostname)
		{
			$this->hostname = $hostname;

			if ($port_number)
			{
				$this->port_number = $port_number;
				$this->hostname .= ':' . (string) $this->port_number;
			}
		}

		return $this;
	}

	/**
	 * Set the resource prefix to use. This method is useful for alternate services that are API-compatible
	 * with AWS.
	 *
	 * @param string $prefix (Required) An alternate prefix to prepend to the resource path. Useful for mock or test applications.
	 * @return $this A reference to the current instance.
	 */
	public function set_resource_prefix($prefix)
	{
		$this->resource_prefix = $prefix;
		return $this;
	}

	/**
	 * Disables any subsequent use of the <set_hostname()> method.
	 *
	 * @param boolean $override (Optional) Whether or not subsequent calls to <set_hostname()> should be obeyed. A `false` value disables the further effectiveness of <set_hostname()>. Defaults to `true`.
	 * @return $this A reference to the current instance.
	 */
	public function allow_hostname_override($override = true)
	{
		$this->override_hostname = $override;
		return $this;
	}

	/**
	 * Disables SSL/HTTPS connections for hosts that don't support them. Some services, however, still
	 * require SSL support.
	 *
	 * This method will throw a user warning when invoked, which can be hidden by changing your
	 * <php:error_reporting()> settings.
	 *
	 * @return $this A reference to the current instance.
	 */
	public function disable_ssl()
	{
		trigger_error('Disabling SSL connections is potentially unsafe and highly discouraged.', E_USER_WARNING);
		$this->use_ssl = false;
		return $this;
	}

	/**
	 * Disables the verification of the SSL Certificate Authority. Doing so can enable an attacker to carry
	 * out a man-in-the-middle attack.
	 *
	 * https://secure.wikimedia.org/wikipedia/en/wiki/Man-in-the-middle_attack
	 *
	 * This method will throw a user warning when invoked, which can be hidden by changing your
	 * <php:error_reporting()> settings.
	 *
	 * @return $this A reference to the current instance.
	 */
	public function disable_ssl_verification($ssl_verification = false)
	{
		trigger_error('Disabling the verification of SSL certificates can lead to man-in-the-middle attacks. It is potentially unsafe and highly discouraged.', E_USER_WARNING);
		$this->ssl_verification = $ssl_verification;
		return $this;
	}

	/**
	 * Enables HTTP request/response header logging to `STDERR`.
	 *
	 * @param boolean $enabled (Optional) Whether or not to enable debug mode. Defaults to `true`.
	 * @return $this A reference to the current instance.
	 */
	public function enable_debug_mode($enabled = true)
	{
		$this->debug_mode = $enabled;
		return $this;
	}

	/**
	 * Sets the maximum number of times to retry failed requests.
	 *
	 * @param integer $retries (Optional) The maximum number of times to retry failed requests. Defaults to `3`.
	 * @return $this A reference to the current instance.
	 */
	public function set_max_retries($retries = 3)
	{
		$this->max_retries = $retries;
		return $this;
	}

	/**
	 * Set the caching configuration to use for response caching.
	 *
	 * @param string $location (Required) <p>The location to store the cache object in. This may vary by cache method.</p><ul><li>File - The local file system paths such as <code>./cache</code> (relative) or <code>/tmp/cache/</code> (absolute). The location must be server-writable.</li><li>APC - Pass in <code>apc</code> to use this lightweight cache. You must have the <a href="http://php.net/apc">APC extension</a> installed.</li><li>XCache - Pass in <code>xcache</code> to use this lightweight cache. You must have the <a href="http://xcache.lighttpd.net">XCache</a> extension installed.</li><li>Memcached - Pass in an indexed array of associative arrays. Each associative array should have a <code>host</code> and a <code>port</code> value representing a <a href="http://php.net/memcached">Memcached</a> server to connect to.</li><li>PDO - A URL-style string (e.g. <code>pdo.mysql://user:pass@localhost/cache</code>) or a standard DSN-style string (e.g. <code>pdo.sqlite:/sqlite/cache.db</code>). MUST be prefixed with <code>pdo.</code>. See <code>CachePDO</code> and <a href="http://php.net/pdo">PDO</a> for more details.</li></ul>
	 * @param boolean $gzip (Optional) Whether or not data should be gzipped before being stored. A value of `true` will compress the contents before caching them. A value of `false` will leave the contents uncompressed. Defaults to `true`.
	 * @return $this A reference to the current instance.
	 */
	public function set_cache_config($location, $gzip = true)
	{
		// If we have an array, we're probably passing in Memcached servers and ports.
		if (is_array($location))
		{
			$this->cache_class = 'CacheMC';
		}
		else
		{
			// I would expect locations like `/tmp/cache`, `pdo.mysql://user:pass@hostname:port`, `pdo.sqlite:memory:`, and `apc`.
			$type = strtolower(substr($location, 0, 3));
			switch ($type)
			{
				case 'apc':
					$this->cache_class = 'CacheAPC';
					break;

				case 'xca': // First three letters of `xcache`
					$this->cache_class = 'CacheXCache';
					break;

				case 'pdo':
					$this->cache_class = 'CachePDO';
					$location = substr($location, 4);
					break;

				default:
					$this->cache_class = 'CacheFile';
					break;
			}
		}

		// Set the remaining cache information.
		$this->cache_location = $location;
		$this->cache_compress = $gzip;

		return $this;
	}

	/**
	 * Register a callback function to execute whenever a data stream is read from using
	 * <CFRequest::streaming_read_callback()>.
	 *
	 * The user-defined callback function should accept three arguments:
	 *
	 * <ul>
	 * 	<li><code>$curl_handle</code> - <code>resource</code> - Required - The cURL handle resource that represents the in-progress transfer.</li>
	 * 	<li><code>$file_handle</code> - <code>resource</code> - Required - The file handle resource that represents the file on the local file system.</li>
	 * 	<li><code>$length</code> - <code>integer</code> - Required - The length in kilobytes of the data chunk that was transferred.</li>
	 * </ul>
	 *
	 * @param string|array|function $callback (Required) The callback function is called by <php:call_user_func()>, so you can pass the following values: <ul>
	 * 	<li>The name of a global function to execute, passed as a string.</li>
	 * 	<li>A method to execute, passed as <code>array('ClassName', 'MethodName')</code>.</li>
	 * 	<li>An anonymous function (PHP 5.3+).</li></ul>
	 * @return $this A reference to the current instance.
	 */
	public function register_streaming_read_callback($callback)
	{
		$this->registered_streaming_read_callback = $callback;
		return $this;
	}

	/**
	 * Register a callback function to execute whenever a data stream is written to using
	 * <CFRequest::streaming_write_callback()>.
	 *
	 * The user-defined callback function should accept two arguments:
	 *
	 * <ul>
	 * 	<li><code>$curl_handle</code> - <code>resource</code> - Required - The cURL handle resource that represents the in-progress transfer.</li>
	 * 	<li><code>$length</code> - <code>integer</code> - Required - The length in kilobytes of the data chunk that was transferred.</li>
	 * </ul>
	 *
	 * @param string|array|function $callback (Required) The callback function is called by <php:call_user_func()>, so you can pass the following values: <ul>
	 * 	<li>The name of a global function to execute, passed as a string.</li>
	 * 	<li>A method to execute, passed as <code>array('ClassName', 'MethodName')</code>.</li>
	 * 	<li>An anonymous function (PHP 5.3+).</li></ul>
	 * @return $this A reference to the current instance.
	 */
	public function register_streaming_write_callback($callback)
	{
		$this->registered_streaming_write_callback = $callback;
		return $this;
	}

	/**
	 * Fetches and caches STS credentials. This is meant to be used by the constructor, and is not to be
	 * manually invoked.
	 *
	 * @param CacheCore $cache (Required) The a reference to the cache object that is being used to handle the caching.
	 * @param array $options (Required) The options that were passed into the constructor.
	 * @return mixed The data to be cached, or NULL.
	 */
	public function cache_sts_credentials($cache, $options)
	{
		$token = new AmazonSTS($options);
		$response = $token->get_session_token();

		if ($response->isOK())
		{
			// Update the expiration
			$expiration_time = strtotime((string) $response->body->GetSessionTokenResult->Credentials->Expiration);
			$expiration_duration = round(($expiration_time - time()) * 0.85);
			$cache->expire_in($expiration_duration);

			// Return the important data
			return array(
				'key'     => (string) $response->body->GetSessionTokenResult->Credentials->AccessKeyId,
				'secret'  => (string) $response->body->GetSessionTokenResult->Credentials->SecretAccessKey,
				'token'   => (string) $response->body->GetSessionTokenResult->Credentials->SessionToken,
				'expires' => (string) $response->body->GetSessionTokenResult->Credentials->Expiration,
			);
		}

		return null;
	}


	/*%******************************************************************************************%*/
	// SET CUSTOM CLASSES

	/**
	 * Set a custom class for this functionality. Use this method when extending/overriding existing classes
	 * with new functionality.
	 *
	 * The replacement class must extend from <CFUtilities>.
	 *
	 * @param string $class (Optional) The name of the new class to use for this functionality.
	 * @return $this A reference to the current instance.
	 */
	public function set_utilities_class($class = 'CFUtilities')
	{
		$this->utilities_class = $class;
		$this->util = new $this->utilities_class();
		return $this;
	}

	/**
	 * Set a custom class for this functionality. Use this method when extending/overriding existing classes
	 * with new functionality.
	 *
	 * The replacement class must extend from <CFRequest>.
	 *
	 * @param string $class (Optional) The name of the new class to use for this functionality.
	 * @param $this A reference to the current instance.
	 */
	public function set_request_class($class = 'CFRequest')
	{
		$this->request_class = $class;
		return $this;
	}

	/**
	 * Set a custom class for this functionality. Use this method when extending/overriding existing classes
	 * with new functionality.
	 *
	 * The replacement class must extend from <CFResponse>.
	 *
	 * @param string $class (Optional) The name of the new class to use for this functionality.
	 * @return $this A reference to the current instance.
	 */
	public function set_response_class($class = 'CFResponse')
	{
		$this->response_class = $class;
		return $this;
	}

	/**
	 * Set a custom class for this functionality. Use this method when extending/overriding existing classes
	 * with new functionality.
	 *
	 * The replacement class must extend from <CFSimpleXML>.
	 *
	 * @param string $class (Optional) The name of the new class to use for this functionality.
	 * @return $this A reference to the current instance.
	 */
	public function set_parser_class($class = 'CFSimpleXML')
	{
		$this->parser_class = $class;
		return $this;
	}

	/**
	 * Set a custom class for this functionality. Use this method when extending/overriding existing classes
	 * with new functionality.
	 *
	 * The replacement class must extend from <CFBatchRequest>.
	 *
	 * @param string $class (Optional) The name of the new class to use for this functionality.
	 * @return $this A reference to the current instance.
	 */
	public function set_batch_class($class = 'CFBatchRequest')
	{
		$this->batch_class = $class;
		return $this;
	}


	/*%******************************************************************************************%*/
	// AUTHENTICATION

	/**
	 * Default, shared method for authenticating a connection to AWS.
	 *
	 * @param string $operation (Required) Indicates the operation to perform.
	 * @param array $payload (Required) An associative array of parameters for authenticating. See the individual methods for allowed keys.
	 * @return CFResponse Object containing a parsed HTTP response.
	 */
	public function authenticate($operation, $payload)
	{
		$original_payload = $payload;
		$method_arguments = func_get_args();
		$curlopts = array();
		$return_curl_handle = false;

		if (substr($operation, 0, strlen($this->operation_prefix)) !== $this->operation_prefix)
		{
			$operation = $this->operation_prefix . $operation;
		}

		// Extract the custom CURLOPT settings from the payload
		if (is_array($payload) && isset($payload['curlopts']))
		{
			$curlopts = $payload['curlopts'];
			unset($payload['curlopts']);
		}

		// Determine whether the response or curl handle should be returned
		if (is_array($payload) && isset($payload['returnCurlHandle']))
		{
			$return_curl_handle = isset($payload['returnCurlHandle']) ? $payload['returnCurlHandle'] : false;
			unset($payload['returnCurlHandle']);
		}

		// Use the caching flow to determine if we need to do a round-trip to the server.
		if ($this->use_cache_flow)
		{
			// Generate an identifier specific to this particular set of arguments.
			$cache_id = $this->key . '_' . get_class($this) . '_' . $operation . '_' . sha1(serialize($method_arguments));

			// Instantiate the appropriate caching object.
			$this->cache_object = new $this->cache_class($cache_id, $this->cache_location, $this->cache_expires, $this->cache_compress);

			if ($this->delete_cache)
			{
				$this->use_cache_flow = false;
				$this->delete_cache = false;
				return $this->cache_object->delete();
			}

			// Invoke the cache callback function to determine whether to pull data from the cache or make a fresh request.
			$data = $this->cache_object->response_manager(array($this, 'cache_callback'), $method_arguments);

			// Parse the XML body
			$data = $this->parse_callback($data);

			// End!
			return $data;
		}

		/*%******************************************************************************************%*/

		// Signer
		$signer = new $this->auth_class($this->hostname, $operation, $payload, $this->credentials);
		$signer->key = $this->key;
		$signer->secret_key = $this->secret_key;
		$signer->auth_token = $this->auth_token;
		$signer->api_version = $this->api_version;
		$signer->utilities_class = $this->utilities_class;
		$signer->request_class = $this->request_class;
		$signer->response_class = $this->response_class;
		$signer->use_ssl = $this->use_ssl;
		$signer->proxy = $this->proxy;
		$signer->util = $this->util;
		$signer->registered_streaming_read_callback = $this->registered_streaming_read_callback;
		$signer->registered_streaming_write_callback = $this->registered_streaming_write_callback;
		$request = $signer->authenticate();

		// Update RequestCore settings
		$request->request_class = $this->request_class;
		$request->response_class = $this->response_class;
		$request->ssl_verification = $this->ssl_verification;

		/*%******************************************************************************************%*/

		// Debug mode
		if ($this->debug_mode)
		{
			$request->debug_mode = $this->debug_mode;
		}

		// Set custom CURLOPT settings
		if (count($curlopts))
		{
			$request->set_curlopts($curlopts);
		}

		// Manage the (newer) batch request API or the (older) returnCurlHandle setting.
		if ($this->use_batch_flow)
		{
			$handle = $request->prep_request();
			$this->batch_object->add($handle);
			$this->use_batch_flow = false;

			return $handle;
		}
		elseif ($return_curl_handle)
		{
			return $request->prep_request();
		}

		// Send!
		$request->send_request();

		// Prepare the response.
		$headers = $request->get_response_header();
		$headers['x-aws-stringtosign'] = $signer->string_to_sign;

		if (isset($signer->canonical_request))
		{
			$headers['x-aws-canonicalrequest'] = $signer->canonical_request;
		}

		$headers['x-aws-request-headers'] = $request->request_headers;
		$headers['x-aws-body'] = $signer->querystring;

		$data = new $this->response_class($headers, ($this->parse_the_response === true) ? $this->parse_callback($request->get_response_body()) : $request->get_response_body(), $request->get_response_code());

		// Was it Amazon's fault the request failed? Retry the request until we reach $max_retries.
		if (
		    (integer) $request->get_response_code() === 500 || // Internal Error (presumably transient)
		    (integer) $request->get_response_code() === 503)   // Service Unavailable (presumably transient)
		{
			if ($this->redirects <= $this->max_retries)
			{
				// Exponential backoff
				$delay = (integer) (pow(4, $this->redirects) * 100000);
				usleep($delay);
				$this->redirects++;
				$data = $this->authenticate($operation, $original_payload);
			}
		}

		// DynamoDB has custom logic
		elseif (
			(integer) $request->get_response_code() === 400 &&
			 stripos((string) $request->get_response_body(), 'com.amazonaws.dynamodb.') !== false && (
				stripos((string) $request->get_response_body(), 'ProvisionedThroughputExceededException') !== false
			)
		)
		{
			if ($this->redirects === 0)
			{
				$this->redirects++;
				$data = $this->authenticate($operation, $original_payload);
			}
			elseif ($this->redirects <= max($this->max_retries, 10))
			{
				// Exponential backoff
				$delay = (integer) (pow(2, ($this->redirects - 1)) * 50000);
				usleep($delay);
				$this->redirects++;
				$data = $this->authenticate($operation, $original_payload);
			}
		}

		$this->redirects = 0;
		return $data;
	}


	/*%******************************************************************************************%*/
	// BATCH REQUEST LAYER

	/**
	 * Specifies that the intended request should be queued for a later batch request.
	 *
	 * @param CFBatchRequest $queue (Optional) The <CFBatchRequest> instance to use for managing batch requests. If not available, it generates a new instance of <CFBatchRequest>.
	 * @return $this A reference to the current instance.
	 */
	public function batch(CFBatchRequest &$queue = null)
	{
		if ($queue)
		{
			$this->batch_object = $queue;
		}
		elseif ($this->internal_batch_object)
		{
			$this->batch_object = &$this->internal_batch_object;
		}
		else
		{
			$this->internal_batch_object = new $this->batch_class();
			$this->batch_object = &$this->internal_batch_object;
		}

		$this->use_batch_flow = true;

		return $this;
	}

	/**
	 * Executes the batch request queue by sending all queued requests.
	 *
	 * @param boolean $clear_after_send (Optional) Whether or not to clear the batch queue after sending a request. Defaults to `true`. Set this to `false` if you are caching batch responses and want to retrieve results later.
	 * @return array An array of <CFResponse> objects.
	 */
	public function send($clear_after_send = true)
	{
		if ($this->use_batch_flow)
		{
			// When we send the request, disable batch flow.
			$this->use_batch_flow = false;

			// If we're not caching, simply send the request.
			if (!$this->use_cache_flow)
			{
				$response = $this->batch_object->send();
				$parsed_data = array_map(array($this, 'parse_callback'), $response);
				$parsed_data = new CFArray($parsed_data);

				// Clear the queue
				if ($clear_after_send)
				{
					$this->batch_object->queue = array();
				}

				return $parsed_data;
			}

			// Generate an identifier specific to this particular set of arguments.
			$cache_id = $this->key . '_' . get_class($this) . '_' . sha1(serialize($this->batch_object));

			// Instantiate the appropriate caching object.
			$this->cache_object = new $this->cache_class($cache_id, $this->cache_location, $this->cache_expires, $this->cache_compress);

			if ($this->delete_cache)
			{
				$this->use_cache_flow = false;
				$this->delete_cache = false;
				return $this->cache_object->delete();
			}

			// Invoke the cache callback function to determine whether to pull data from the cache or make a fresh request.
			$data_set = $this->cache_object->response_manager(array($this, 'cache_callback_batch'), array($this->batch_object));
			$parsed_data = array_map(array($this, 'parse_callback'), $data_set);
			$parsed_data = new CFArray($parsed_data);

			// Clear the queue
			if ($clear_after_send)
			{
				$this->batch_object->queue = array();
			}

			// End!
			return $parsed_data;
		}

		// Load the class
		$null = new CFBatchRequest();
		unset($null);

		throw new CFBatchRequest_Exception('You must use $object->batch()->send()');
	}

	/**
	 * Parses a response body into a PHP object if appropriate.
	 *
	 * @param CFResponse|string $response (Required) The <CFResponse> object to parse, or an XML string that would otherwise be a response body.
	 * @param string $content_type (Optional) The content-type to use when determining how to parse the content.
	 * @return CFResponse|string A parsed <CFResponse> object, or parsed XML.
	 */
	public function parse_callback($response, $headers = null)
	{
		// Bail out
		if (!$this->parse_the_response) return $response;

		// Shorten this so we have a (mostly) single code path
		if (isset($response->body))
		{
			if (is_string($response->body))
			{
				$body = $response->body;
			}
			else
			{
				return $response;
			}
		}
		elseif (is_string($response))
		{
			$body = $response;
		}
		else
		{
			return $response;
		}

		// Decompress gzipped content
		if (isset($headers['content-encoding']))
		{
			switch (strtolower(trim($headers['content-encoding'], "\x09\x0A\x0D\x20")))
			{
				case 'gzip':
				case 'x-gzip':
					$decoder = new CFGzipDecode($body);
					if ($decoder->parse())
					{
						$body = $decoder->data;
					}
					break;

				case 'deflate':
					if (($uncompressed = gzuncompress($body)) !== false)
					{
						$body = $uncompressed;
					}
					elseif (($uncompressed = gzinflate($body)) !== false)
					{
						$body = $uncompressed;
					}
					break;
			}
		}

		// Look for XML cues
		if (
			(isset($headers['content-type']) && ($headers['content-type'] === 'text/xml' || $headers['content-type'] === 'application/xml')) || // We know it's XML
			(!isset($headers['content-type']) && (stripos($body, '<?xml') === 0 || strpos($body, '<Error>') === 0) || preg_match('/^<(\w*) xmlns="http(s?):\/\/(\w*).amazon(aws)?.com/im', $body)) // Sniff for XML
		)
		{
			// Strip the default XML namespace to simplify XPath expressions
			$body = str_replace("xmlns=", "ns=", $body);

			try {
				// Parse the XML body
				$body = new $this->parser_class($body);
			}
			catch (Exception $e)
			{
				throw new Parser_Exception($e->getMessage());
			}
		}
		// Look for JSON cues
		elseif (
			(isset($headers['content-type']) && ($headers['content-type'] === 'application/json') || $headers['content-type'] === 'application/x-amz-json-1.0') || // We know it's JSON
			(!isset($headers['content-type']) && $this->util->is_json($body)) // Sniff for JSON
		)
		{
			// Normalize JSON to a CFSimpleXML object
			$body = CFJSON::to_xml($body, $this->parser_class);
		}

		// Put the parsed data back where it goes
		if (isset($response->body))
		{
			$response->body = $body;
		}
		else
		{
			$response = $body;
		}

		return $response;
	}


	/*%******************************************************************************************%*/
	// CACHING LAYER

	/**
	 * Specifies that the resulting <CFResponse> object should be cached according to the settings from
	 * <set_cache_config()>.
	 *
	 * @param string|integer $expires (Required) The time the cache is to expire. Accepts a number of seconds as an integer, or an amount of time, as a string, that is understood by <php:strtotime()> (e.g. "1 hour").
	 * @param $this A reference to the current instance.
	 * @return $this
	 */
	public function cache($expires)
	{
		// Die if they haven't used set_cache_config().
		if (!$this->cache_class)
		{
			throw new CFRuntime_Exception('Must call set_cache_config() before using cache()');
		}

		if (is_string($expires))
		{
			$expires = strtotime($expires);
			$this->cache_expires = $expires - time();
		}
		elseif (is_int($expires))
		{
			$this->cache_expires = $expires;
		}

		$this->use_cache_flow = true;

		return $this;
	}

	/**
	 * The callback function that is executed when the cache doesn't exist or has expired. The response of
	 * this method is cached. Accepts identical parameters as the <authenticate()> method. Never call this
	 * method directly -- it is used internally by the caching system.
	 *
	 * @param string $operation (Required) Indicates the operation to perform.
	 * @param array $payload (Required) An associative array of parameters for authenticating. See the individual methods for allowed keys.
	 * @return CFResponse A parsed HTTP response.
	 */
	public function cache_callback($operation, $payload)
	{
		// Disable the cache flow since it's already been handled.
		$this->use_cache_flow = false;

		// Make the request
		$response = $this->authenticate($operation, $payload);

		// If this is an XML document, convert it back to a string.
		if (isset($response->body) && ($response->body instanceof SimpleXMLElement))
		{
			$response->body = $response->body->asXML();
		}

		return $response;
	}

	/**
	 * Used for caching the results of a batch request. Never call this method directly; it is used
	 * internally by the caching system.
	 *
	 * @param CFBatchRequest $batch (Required) The batch request object to send.
	 * @return CFResponse A parsed HTTP response.
	 */
	public function cache_callback_batch(CFBatchRequest $batch)
	{
		return $batch->send();
	}

	/**
	 * Deletes a cached <CFResponse> object using the specified cache storage type.
	 *
	 * @return boolean A value of `true` if cached object exists and is successfully deleted, otherwise `false`.
	 */
	public function delete_cache()
	{
		$this->use_cache_flow = true;
		$this->delete_cache = true;

		return $this;
	}
}


/**
 * Contains the functionality for auto-loading service classes.
 */
class CFLoader
{
	/*%******************************************************************************************%*/
	// AUTO-LOADER

	/**
	 * Automatically load classes that aren't included.
	 *
	 * @param string $class (Required) The classname to load.
	 * @return boolean Whether or not the file was successfully loaded.
	 */
	public static function autoloader($class)
	{
		$path = dirname(__FILE__) . DIRECTORY_SEPARATOR;

		// Amazon SDK classes
		if (strstr($class, 'Amazon'))
		{
			if (file_exists($require_this = $path . 'services' . DIRECTORY_SEPARATOR . str_ireplace('Amazon', '', strtolower($class)) . '.class.php'))
			{
				require_once $require_this;
				return true;
			}

			return false;
		}

		// Utility classes
		elseif (strstr($class, 'CF'))
		{
			if (file_exists($require_this = $path . 'utilities' . DIRECTORY_SEPARATOR . str_ireplace('CF', '', strtolower($class)) . '.class.php'))
			{
				require_once $require_this;
				return true;
			}

			return false;
		}

		// Load CacheCore
		elseif (strstr($class, 'Cache'))
		{
			if (file_exists($require_this = $path . 'lib' . DIRECTORY_SEPARATOR . 'cachecore' . DIRECTORY_SEPARATOR . strtolower($class) . '.class.php'))
			{
				require_once $require_this;
				return true;
			}

			return false;
		}

		// Load RequestCore
		elseif (strstr($class, 'RequestCore') || strstr($class, 'ResponseCore'))
		{
			if (file_exists($require_this = $path . 'lib' . DIRECTORY_SEPARATOR . 'requestcore' . DIRECTORY_SEPARATOR . 'requestcore.class.php'))
			{
				require_once $require_this;
				return true;
			}

			return false;
		}

		// Load array-to-domdocument
		elseif (strstr($class, 'Array2DOM'))
		{
			if (file_exists($require_this = $path . 'lib' . DIRECTORY_SEPARATOR . 'dom' . DIRECTORY_SEPARATOR . 'ArrayToDOMDocument.php'))
			{
				require_once $require_this;
				return true;
			}

			return false;
		}

		// Load Authentication Signers
		elseif (strstr($class, 'Auth'))
		{
			if (file_exists($require_this = $path . 'authentication' . DIRECTORY_SEPARATOR . str_replace('auth', 'signature_', strtolower($class)) . '.class.php'))
			{
				require_once $require_this;
				return true;
			}

			return false;
		}

		// Load Signer interface
		elseif ($class === 'Signer')
		{
			if (!interface_exists('Signable', false) &&
			    file_exists($require_this = $path . 'authentication' . DIRECTORY_SEPARATOR . 'signable.interface.php'))
			{
				require_once $require_this;
			}

			if (file_exists($require_this = $path . 'authentication' . DIRECTORY_SEPARATOR . 'signer.abstract.php'))
			{
				require_once $require_this;
				return true;
			}

			return false;
		}

		// Load Symfony YAML classes
		elseif (strstr($class, 'sfYaml'))
		{
			if (file_exists($require_this = $path . 'lib' . DIRECTORY_SEPARATOR . 'yaml' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'sfYaml.php'))
			{
				require_once $require_this;
				return true;
			}

			return false;
		}

		return false;
	}
}

// Register the autoloader.
spl_autoload_register(array('CFLoader', 'autoloader'));

// Don't look for any configuration files, the Amazon S3 storage backend handles configuration

// /*%******************************************************************************************%*/
// // CONFIGURATION
// 
// // Look for include file in the same directory (e.g. `./config.inc.php`).
// if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.inc.php'))
// {
// 	include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.inc.php';
// }
// // Fallback to `~/.aws/sdk/config.inc.php`
// else
// {
// 	if (!isset($_ENV['HOME']) && isset($_SERVER['HOME']))
// 	{
// 		$_ENV['HOME'] = $_SERVER['HOME'];
// 	}
// 	elseif (!isset($_ENV['HOME']) && !isset($_SERVER['HOME']))
// 	{
// 		$_ENV['HOME'] = `cd ~ && pwd`;
// 		if (!$_ENV['HOME'])
// 		{
// 			switch (strtolower(PHP_OS))
// 			{
// 				case 'darwin':
// 					$_ENV['HOME'] = '/Users/' . get_current_user();
// 					break;
// 
// 				case 'windows':
// 				case 'winnt':
// 				case 'win32':
// 					$_ENV['HOME'] = 'c:' . DIRECTORY_SEPARATOR . 'Documents and Settings' . DIRECTORY_SEPARATOR . get_current_user();
// 					break;
// 
// 				default:
// 					$_ENV['HOME'] = '/home/' . get_current_user();
// 					break;
// 			}
// 		}
// 	}
// 
// 	if (getenv('HOME') && file_exists(getenv('HOME') . DIRECTORY_SEPARATOR . '.aws' . DIRECTORY_SEPARATOR . 'sdk' . DIRECTORY_SEPARATOR . 'config.inc.php'))
// 	{
// 		include_once getenv('HOME') . DIRECTORY_SEPARATOR . '.aws' . DIRECTORY_SEPARATOR . 'sdk' . DIRECTORY_SEPARATOR . 'config.inc.php';
// 	}
// }
