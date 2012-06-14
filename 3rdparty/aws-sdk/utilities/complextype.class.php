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
 * Contains utility methods used for converting array, JSON, and YAML data types into query string keys.
 *
 * @version 2010.11.11
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFComplexType
{
	/**
	 * Takes a JSON object, as a string, to convert to query string keys.
	 *
	 * @param string $json (Required) A JSON object. The JSON string should use canonical rules (e.g., double quotes, quoted keys) as is required by PHP's <php:json_encode()> function.
	 * @param string $member (Optional) The name of the "member" property that AWS uses for lists in certain services. Defaults to an empty string.
	 * @param string $default_key (Optional) The default key to use when the value for `$data` is a string. Defaults to an empty string.
	 * @return array The option group parameters to merge into another method's `$opt` parameter.
	 */
	public static function json($json, $member = '', $default_key = '')
	{
		return self::option_group(json_decode($json, true), $member, $default_key);
	}

	/**
	 * Takes a YAML object, as a string, to convert to query string keys.
	 *
	 * @param string $yaml (Required) A YAML object.
	 * @param string $member (Optional) The name of the "member" property that AWS uses for lists in certain services. Defaults to an empty string.
	 * @param string $default_key (Optional) The default key to use when the value for `$data` is a string. Defaults to an empty string.
	 * @return array The option group parameters to merge into another method's `$opt` parameter.
	 */
	public static function yaml($yaml, $member = '', $default_key = '')
	{
		return self::option_group(sfYaml::load($yaml), $member, $default_key);
	}

	/**
	 * Takes an associative array to convert to query string keys.
	 *
	 * @param array $map (Required) An associative array.
	 * @param string $member (Optional) The name of the "member" property that AWS uses for lists in certain services. Defaults to an empty string.
	 * @param string $default_key (Optional) The default key to use when the value for `$data` is a string. Defaults to an empty string.
	 * @return array The option group parameters to merge into another method's `$opt` parameter.
	 */
	public static function map($map, $member = '', $default_key = '')
	{
		return self::option_group($map, $member, $default_key);
	}

	/**
	 * A protected method that is used by <json()>, <yaml()> and <map()>.
	 *
	 * @param string|array $data (Required) The data to iterate over.
	 * @param string $member (Optional) The name of the "member" property that AWS uses for lists in certain services. Defaults to an empty string.
	 * @param string $key (Optional) The default key to use when the value for `$data` is a string. Defaults to an empty string.
	 * @param array $out (Optional) INTERNAL ONLY. The array that contains the calculated values up to this point.
	 * @return array The option group parameters to merge into another method's `$opt` parameter.
	 */
	public static function option_group($data, $member = '', $key = '', &$out = array())
	{
		$reset = $key;

		if (is_array($data))
		{
			foreach ($data as $k => $v)
			{
				// Avoid 0-based indexes.
				if (is_int($k))
				{
					$k = $k + 1;

					if ($member !== '')
					{
						$key .= '.' . $member;
					}
				}

				$key .= ($key === '' ? $k : '.' . $k);

				if (is_array($v))
				{
					self::option_group($v, $member, $key, $out);
				}
				elseif ($v instanceof CFStepConfig)
				{
					self::option_group($v->get_config(), $member, $key, $out);
				}
				else
				{
					$out[$key] = $v;
				}

				$key = $reset;
			}
		}
		else
		{
			$out[$key] = $data;
		}

		return $out;
	}
}
