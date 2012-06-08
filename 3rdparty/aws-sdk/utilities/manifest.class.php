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
 * Simplifies the process of generating manifests for the AWS Import/Export service.
 *
 * @version 2010.11.22
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFManifest
{

	/**
	 * Takes a JSON object as a string to convert to a YAML manifest.
	 *
	 * @param string $json (Required) A JSON object. The JSON string should use canonical rules (e.g., double quotes, quoted keys) as is required by PHP's <php:json_encode()> function.
	 * @return string A YAML manifest document.
	 */
	public static function json($json)
	{
		$map = json_decode($json, true);
		return sfYaml::dump($map);
	}

	/**
	 * Takes an associative array to convert to a YAML manifest.
	 *
	 * @param array $map (Required) An associative array.
	 * @return string A YAML manifest document.
	 */
	public static function map($map)
	{
		return sfYaml::dump($map);
	}
}
