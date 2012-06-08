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
 * Contains core functionality for Hadoop helpers.
 *
 * @version 2011.05.03
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 * @link http://hadoop.apache.org Apache Hadoop
 */
class CFHadoopBase
{
	/**
	 * Runs a specified script on the master node of your cluster.
	 *
	 * @param string $script (Required) The script to run with `script-runner.jar`.
	 * @param array $args (Optional) An indexed array of arguments to pass to the script.
	 * @return array A standard array that is intended to be passed into a <CFStepConfig> object.
	 */
	public static function script_runner($script, $args = null)
	{
		if (!$args) $args = array();
		array_unshift($args, $script);

		return array(
			'Jar' => 's3://us-east-1.elasticmapreduce/libs/script-runner/script-runner.jar',
			'Args' => $args
		);
	}

	/**
	 * Prepares a Hive or Pig script before passing it to the script runner.
	 *
	 * @param string $type (Required) The type of script to run. [Allowed values: `hive`, `pig`].
	 * @param array $args (Optional) An indexed array of arguments to pass to the script.
	 * @return array A standard array that is intended to be passed into a <CFStepConfig> object.
	 * @link http://hive.apache.org Apache Hive
	 * @link http://pig.apache.org Apache Pig
	 */
	public static function hive_pig_script($type, $args = null)
	{
		if (!$args) $args = array();
		$args = is_array($args) ? $args : array($args);
		$args = array_merge(array('--base-path', 's3://us-east-1.elasticmapreduce/libs/' . $type . '/'), $args);

        return self::script_runner('s3://us-east-1.elasticmapreduce/libs/' . $type . '/' . $type . '-script', $args);
	}
}
