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
// CLASS

/**
 * Contains a set of utility methods for connecting to, and working with, AWS.
 *
 * @version 2010.09.30
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFUtilities
{

	/*%******************************************************************************************%*/
	// CONSTANTS

	/**
	 * Define the RFC 2616-compliant date format.
	 */
	const DATE_FORMAT_RFC2616 = 'D, d M Y H:i:s \G\M\T';

	/**
	 * Define the ISO-8601-compliant date format.
	 */
	const DATE_FORMAT_ISO8601 = 'Y-m-d\TH:i:s\Z';

	/**
	 * Define the MySQL-compliant date format.
	 */
	const DATE_FORMAT_MYSQL = 'Y-m-d H:i:s';

	/**
	 * Define the Signature v4 date format.
	 */
	const DATE_FORMAT_SIGV4 = 'Ymd\THis\Z';


	/*%******************************************************************************************%*/
	// METHODS

	/**
	 * Constructs a new instance of this class.
	 *
	 * @return $this A reference to the current instance.
	 */
	public function __construct()
	{
		return $this;
	}

	/**
	 * Retrieves the value of a class constant, while avoiding the `T_PAAMAYIM_NEKUDOTAYIM` error. Misspelled because `const` is a reserved word.
	 *
	 * @param object $class (Required) An instance of the class containing the constant.
	 * @param string $const (Required) The name of the constant to retrieve.
	 * @return mixed The value of the class constant.
	 */
	public function konst($class, $const)
	{
		if (is_string($class))
		{
			$ref = new ReflectionClass($class);
		}
		else
		{
			$ref = new ReflectionObject($class);
		}

		return $ref->getConstant($const);
	}

	/**
	 * Convert a HEX value to Base64.
	 *
	 * @param string $str (Required) Value to convert.
	 * @return string Base64-encoded string.
	 */
	public function hex_to_base64($str)
	{
		$raw = '';

		for ($i = 0; $i < strlen($str); $i += 2)
		{
			$raw .= chr(hexdec(substr($str, $i, 2)));
		}

		return base64_encode($raw);
	}

	/**
	 * Convert an associative array into a query string.
	 *
	 * @param array $array (Required) Array to convert.
	 * @return string URL-friendly query string.
	 */
	public function to_query_string($array)
	{
		$temp = array();

		foreach ($array as $key => $value)
		{
			if (is_string($key) && !is_array($value))
			{
				$temp[] = rawurlencode($key) . '=' . rawurlencode($value);
			}
		}

		return implode('&', $temp);
	}

	/**
	 * Convert an associative array into a sign-able string.
	 *
	 * @param array $array (Required) Array to convert.
	 * @return string URL-friendly sign-able string.
	 */
	public function to_signable_string($array)
	{
		$t = array();

		foreach ($array as $k => $v)
		{
			$t[] = $this->encode_signature2($k) . '=' . $this->encode_signature2($v);
		}

		return implode('&', $t);
	}

	/**
	 * Encode the value according to RFC 3986.
	 *
	 * @param string $string (Required) String to convert.
	 * @return string URL-friendly sign-able string.
	 */
	public function encode_signature2($string)
	{
		$string = rawurlencode($string);
		return str_replace('%7E', '~', $string);
	}

	/**
	 * Convert a query string into an associative array. Multiple, identical keys will become an indexed array.
	 *
	 * @param string $qs (Required) Query string to convert.
	 * @return array Associative array of keys and values.
	 */
	public function query_to_array($qs)
	{
		$query = explode('&', $qs);
		$data = array();

		foreach ($query as $q)
		{
			$q = explode('=', $q);

			if (isset($data[$q[0]]) && is_array($data[$q[0]]))
			{
				$data[$q[0]][] = urldecode($q[1]);
			}
			else if (isset($data[$q[0]]) && !is_array($data[$q[0]]))
			{
				$data[$q[0]] = array($data[$q[0]]);
				$data[$q[0]][] = urldecode($q[1]);
			}
			else
			{
				$data[urldecode($q[0])] = urldecode($q[1]);
			}
		}
		return $data;
	}

	/**
	 * Return human readable file sizes.
	 *
	 * @author Aidan Lister <aidan@php.net>
	 * @author Ryan Parman <ryan@getcloudfusion.com>
	 * @license http://www.php.net/license/3_01.txt PHP License
	 * @param integer $size (Required) Filesize in bytes.
	 * @param string $unit (Optional) The maximum unit to use. Defaults to the largest appropriate unit.
	 * @param string $default (Optional) The format for the return string. Defaults to `%01.2f %s`.
	 * @return string The human-readable file size.
	 * @link http://aidanlister.com/repos/v/function.size_readable.php Original Function
	 */
	public function size_readable($size, $unit = null, $default = null)
	{
		// Units
		$sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB');
		$mod = 1024;
		$ii = count($sizes) - 1;

		// Max unit
		$unit = array_search((string) $unit, $sizes);
		if ($unit === null || $unit === false)
		{
			$unit = $ii;
		}

		// Return string
		if ($default === null)
		{
			$default = '%01.2f %s';
		}

		// Loop
		$i = 0;
		while ($unit != $i && $size >= 1024 && $i < $ii)
		{
			$size /= $mod;
			$i++;
		}

		return sprintf($default, $size, $sizes[$i]);
	}

	/**
	 * Convert a number of seconds into Hours:Minutes:Seconds.
	 *
	 * @param integer $seconds (Required) The number of seconds to convert.
	 * @return string The formatted time.
	 */
	public function time_hms($seconds)
	{
		$time = '';

		// First pass
		$hours = (int) ($seconds / 3600);
		$seconds = $seconds % 3600;
		$minutes = (int) ($seconds / 60);
		$seconds = $seconds % 60;

		// Cleanup
		$time .= ($hours) ? $hours . ':' : '';
		$time .= ($minutes < 10 && $hours > 0) ? '0' . $minutes : $minutes;
		$time .= ':';
		$time .= ($seconds < 10) ? '0' . $seconds : $seconds;

		return $time;
	}

	/**
	 * Returns the first value that is set. Based on [Try.these()](http://api.prototypejs.org/language/Try/these/) from [Prototype](http://prototypejs.org).
	 *
	 * @param array $attrs (Required) The attributes to test, as strings. Intended for testing properties of the $base object, but also works with variables if you place an @ symbol at the beginning of the command.
	 * @param object $base (Optional) The base object to use, if any.
	 * @param mixed $default (Optional) What to return if there are no matches. Defaults to `null`.
	 * @return mixed Either a matching property of a given object, boolean `false`, or any other data type you might choose.
	 */
	public function try_these($attrs, $base = null, $default = null)
	{
		if ($base)
		{
			foreach ($attrs as $attr)
			{
				if (isset($base->$attr))
				{
					return $base->$attr;
				}
			}
		}
		else
		{
			foreach ($attrs as $attr)
			{
				if (isset($attr))
				{
					return $attr;
				}
			}
		}

		return $default;
	}

	/**
	 * Can be removed once all calls are updated.
	 *
	 * @deprecated Use <php:json_encode()> instead.
	 * @param mixed $obj (Required) The PHP object to convert into a JSON string.
	 * @return string A JSON string.
	 */
	public function json_encode($obj)
	{
		return json_encode($obj);
	}

	/**
	 * Converts a SimpleXML response to an array structure.
	 *
	 * @param ResponseCore $response (Required) A response value.
	 * @return array The response value as a standard, multi-dimensional array.
	 */
	public function convert_response_to_array(ResponseCore $response)
	{
		return json_decode(json_encode($response), true);
	}

	/**
	 * Checks to see if a date stamp is ISO-8601 formatted, and if not, makes it so.
	 *
	 * @param string $datestamp (Required) A date stamp, or a string that can be parsed into a date stamp.
	 * @return string An ISO-8601 formatted date stamp.
	 */
	public function convert_date_to_iso8601($datestamp)
	{
		if (!preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}((\+|-)\d{2}:\d{2}|Z)/m', $datestamp))
		{
			return gmdate(self::DATE_FORMAT_ISO8601, strtotime($datestamp));
		}

		return $datestamp;
	}

	/**
	 * Determines whether the data is Base64 encoded or not.
	 *
	 * @license http://us.php.net/manual/en/function.base64-decode.php#81425 PHP License
	 * @param string $s (Required) The string to test.
	 * @return boolean Whether the string is Base64 encoded or not.
	 */
	public function is_base64($s)
	{
		return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s);
	}

	/**
	 * Determines whether the data is a JSON string or not.
	 *
	 * @param string $s (Required) The string to test.
	 * @return boolean Whether the string is a valid JSON object or not.
	 */
	public function is_json($s)
	{
		return !!(json_decode($s) instanceof stdClass);
	}

	/**
	 * Decodes `\uXXXX` entities into their real unicode character equivalents.
	 *
	 * @param string $s (Required) The string to decode.
	 * @return string The decoded string.
	 */
	public function decode_uhex($s)
	{
		preg_match_all('/\\\u([0-9a-f]{4})/i', $s, $matches);
		$matches = $matches[count($matches) - 1];
		$map = array();

		foreach ($matches as $match)
		{
			if (!isset($map[$match]))
			{
				$map['\u' . $match] = html_entity_decode('&#' . hexdec($match) . ';', ENT_NOQUOTES, 'UTF-8');
			}
		}

		return str_replace(array_keys($map), $map, $s);
	}

	/**
	 * Generates a random GUID.
	 *
	 * @author Alix Axel <http://www.php.net/manual/en/function.com-create-guid.php#99425>
	 * @license http://www.php.net/license/3_01.txt PHP License
	 * @return string A random GUID.
	 */
	public function generate_guid()
	{
	    return sprintf(
			'%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
			mt_rand(0, 65535),
			mt_rand(0, 65535),
			mt_rand(0, 65535),
			mt_rand(16384, 20479),
			mt_rand(32768, 49151),
			mt_rand(0, 65535),
			mt_rand(0, 65535),
			mt_rand(0, 65535)
		);
	}
}
