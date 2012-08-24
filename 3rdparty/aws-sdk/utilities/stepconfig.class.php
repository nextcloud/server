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
 * Contains functionality for simplifying Amazon EMR Hadoop steps.
 *
 * @version 2010.11.16
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFStepConfig
{

	/**
	 * Stores the configuration map.
	 */
	public $config;

	/**
	 * Constructs a new instance of this class.
	 *
	 * @param array $config (Required) An associative array representing the Hadoop step configuration.
	 * @return $this A reference to the current instance.
	 */
	public function __construct($config)
	{
		// Handle Hadoop jar arguments
		if (isset($config['HadoopJarStep']['Args']) && $args = $config['HadoopJarStep']['Args'])
		{
			$config['HadoopJarStep']['Args'] = is_array($args) ? $args : array($args);
		}

		$this->config = $config;
	}

	/**
	 * Constructs a new instance of this class, and allows chaining.
	 *
	 * @param array $config (Required) An associative array representing the Hadoop step configuration.
	 * @return $this A reference to the current instance.
	 */
	public static function init($config)
	{
		if (version_compare(PHP_VERSION, '5.3.0', '<'))
		{
			throw new Exception('PHP 5.3 or newer is required to instantiate a new class with CLASS::init().');
		}

		$self = get_called_class();
		return new $self($config);
	}

	/**
	 * Returns a JSON representation of the object when typecast as a string.
	 *
	 * @return string A JSON representation of the object.
	 * @link http://www.php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring PHP Magic Methods
	 */
	public function __toString()
	{
		return json_encode($this->config);
	}

	/**
	 * Returns the configuration data.
	 *
	 * @return array The configuration data.
	 */
	public function get_config()
	{
		return $this->config;
	}
}
