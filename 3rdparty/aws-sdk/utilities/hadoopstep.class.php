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
 * Contains a set of pre-built Amazon EMR Hadoop steps.
 *
 * @version 2011.05.03
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 * @link http://hadoop.apache.org Apache Hadoop
 */
class CFHadoopStep extends CFHadoopBase
{
	/**
	 * When ran as the first step in your job flow, enables the Hadoop debugging UI in the AWS
	 * Management Console.
	 *
	 * @return array A standard array that is intended to be passed into a <CFStepConfig> object.
	 */
	public static function enable_debugging()
	{
		return self::script_runner('s3://us-east-1.elasticmapreduce/libs/state-pusher/0.1/fetch');
	}

	/**
	 * Step that installs Hive on your job flow.
	 *
	 * @return array A standard array that is intended to be passed into a <CFStepConfig> object.
	 * @link http://hive.apache.org Apache Hive
	 */
	public static function install_hive()
	{
		return self::hive_pig_script('hive', '--install-hive');
	}

	/**
	 * Step that runs a Hive script on your job flow.
	 *
	 * @param string $script (Required) The script to run with `script-runner.jar`.
	 * @param array $args (Optional) An indexed array of arguments to pass to the script.
	 * @return array A standard array that is intended to be passed into a <CFStepConfig> object.
	 * @link http://hive.apache.org Apache Hive
	 */
	public static function run_hive_script($script, $args = null)
	{
		if (!$args) $args = array();
		$args = is_array($args) ? $args : array($args);
		$args = array_merge(array('--run-hive-script', '--args', '-f', $script), $args);

        return self::hive_pig_script('hive', $args);
	}

	/**
	 * Step that installs Pig on your job flow.
	 *
	 * @return array A standard array that is intended to be passed into a <CFStepConfig> object.
	 * @link http://pig.apache.org Apache Pig
	 */
	public static function install_pig()
	{
		return self::hive_pig_script('pig', '--install-pig');
	}

	/**
	 * Step that runs a Pig script on your job flow.
	 *
	 * @param string $script (Required) The script to run with `script-runner.jar`.
	 * @param array $args (Optional) An indexed array of arguments to pass to the script.
	 * @return array A standard array that is intended to be passed into a <CFStepConfig> object.
	 * @link http://pig.apache.org Apache Pig
	 */
	public static function run_pig_script($script, $args = null)
	{
		if (!$args) $args = array();
		$args = is_array($args) ? $args : array($args);
		$args = array_merge(array('--run-pig-script', '--args', '-f', $script), $args);

        return self::hive_pig_script('pig', $args);
	}
}
