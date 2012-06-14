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
 * The <CFCredentials> class enables developers to easily switch between multiple sets of credentials.
 *
 * @version 2011.11.15
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFCredentials
{
	/**
	 * The key used to specify the default credential set
	 */
	const DEFAULT_KEY = '@default';

	/**
	 * The key used to identify inherited credentials
	 */
	const INHERIT_KEY = '@inherit';

	/**
	 * Stores the credentials
	 */
	protected static $credentials = array();

	/**
	 * Prevents this class from being constructed
	 */
	final private function __construct() {}

	/**
	 * Stores the credentials for re-use.
	 *
	 * @param array $credential_sets (Required) The named credential sets that should be made available to the application.
	 * @return void
	 */
	public static function set(array $credential_sets)
	{
		// Make sure a default credential set is specified or can be inferred
		if (count($credential_sets) === 1)
		{
			$credential_sets[self::DEFAULT_KEY] = reset($credential_sets);
		}
		elseif (!isset($credential_sets[self::DEFAULT_KEY]))
		{
			throw new CFCredentials_Exception('If more than one credential set is provided, a default credential set (identified by the key "' . self::DEFAULT_KEY . '") must be specified.');
		}

		// Resolve any @inherit tags
		foreach ($credential_sets as $credential_name => &$credential_set)
		{
			if (is_array($credential_set))
			{
				foreach ($credential_set as $credential_key => &$credential_value)
				{
					if ($credential_key === self::INHERIT_KEY)
					{
						if (!isset($credential_sets[$credential_value]))
						{
							throw new CFCredentials_Exception('The credential set, "' . $credential_value . '", does not exist and cannot be inherited.');
						}

						$credential_set = array_merge($credential_sets[$credential_value], $credential_set);
						unset($credential_set[self::INHERIT_KEY]);
					}
				}
			}
		}

		// Normalize the value of the @default credential set
		$default = $credential_sets[self::DEFAULT_KEY];
		if (is_string($default))
		{
			if (!isset($credential_sets[$default]))
			{
				throw new CFCredentials_Exception('The credential set, "' . $default . '", does not exist and cannot be used as the default credential set.');
			}

			$credential_sets[self::DEFAULT_KEY] = $credential_sets[$default];
		}

		// Store the credentials
		self::$credentials = $credential_sets;
	}

	/**
	 * Retrieves the requested credentials from the internal credential store.
	 *
	 * @param string $credential_set (Optional) The name of the credential set to retrieve. The default value is set in DEFAULT_KEY.
	 * @return stdClass A stdClass object where the properties represent the keys that were provided.
	 */
	public static function get($credential_name = self::DEFAULT_KEY)
	{
		// Make sure the credential set exists
		if (!isset(self::$credentials[$credential_name]))
		{
			throw new CFCredentials_Exception('The credential set, "' . $credential_name . '", does not exist and cannot be retrieved.');
		}

		// Return the credential set as an object
		return new CFCredential(self::$credentials[$credential_name]);
	}
}

class CFCredentials_Exception extends Exception {}
