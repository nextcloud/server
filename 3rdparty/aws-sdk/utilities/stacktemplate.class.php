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
 * Simplifies the process of preparing JSON stack templates.
 *
 * @version 2011.02.03
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFStackTemplate
{
	/**
	 * Removes whitespace from a JSON template.
	 *
	 * @param string $template (Required) A JSON representation of the stack template. Must have <a href="http://docs.php.net/manual/en/function.json-decode.php#refsect1-function.json-decode-examples">strict JSON-specific formatting</a>.
	 * @return string A JSON representation of the template.
	 */
	public static function json($template)
	{
		return json_encode(json_decode($template, true));
	}

	/**
	 * Converts an associative array (map) of the template into a JSON string.
	 *
	 * @param array $template (Required) An associative array that maps directly to its JSON counterpart.
	 * @return string A JSON representation of the template.
	 */
	public static function map($template)
	{
		return json_encode($template);
	}
}
