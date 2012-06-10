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
 * Contains a series of methods that provide information about the SDK.
 *
 * @version 2010.10.01
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFInfo
{
	/**
	 * Gets information about the web service APIs that the SDK supports.
	 *
	 * @return array An associative array containing service classes and API versions.
	 */
	public static function api_support()
	{
		$existing_classes = get_declared_classes();

		foreach (glob(dirname(dirname(__FILE__)) . '/services/*.class.php') as $file)
		{
			include $file;
		}

		$with_sdk_classes = get_declared_classes();
		$new_classes = array_diff($with_sdk_classes, $existing_classes);
		$filtered_classes = array();
		$collect = array();

		foreach ($new_classes as $class)
		{
			if (strpos($class, 'Amazon') !== false)
			{
				$filtered_classes[] = $class;
			}
		}

		$filtered_classes = array_values($filtered_classes);

		foreach ($filtered_classes as $class)
		{
			$obj = new $class();
			$collect[get_class($obj)] = $obj->api_version;
			unset($obj);
		}

		return $collect;
	}
}
