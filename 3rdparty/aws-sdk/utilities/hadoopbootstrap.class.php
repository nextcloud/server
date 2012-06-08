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
 * Contains a set of pre-built Amazon EMR Hadoop Bootstrap Actions.
 *
 * @version 2011.05.03
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 * @link http://hadoop.apache.org Apache Hadoop
 */
class CFHadoopBootstrap extends CFHadoopBase
{
	// Config file types
	const CONFIG_SITE = 'S';
	const CONFIG_DEFAULT = 'D';
	const CONFIG_CORE = 'C';
	const CONFIG_HDFS = 'H';
	const CONFIG_MAPREDUCE = 'M';

	// Daemon types
	const DAEMON_NAME_NODE = 'namenode';
	const DAEMON_DATA_NODE = 'datanode';
	const DAEMON_JOB_TRACKER = 'jobtracker';
	const DAEMON_TASK_TRACKER = 'tasktracker';
	const DAEMON_CLIENT = 'client';

	/**
	 * Create a new run-if bootstrap action which lets you conditionally run bootstrap actions.
	 *
	 * @param string $condition (Required) The condition to evaluate. If <code>true</code>, the bootstrap action executes.
	 * @param array $args (Optional) An indexed array of arguments to pass to the script.
	 * @return array A configuration set to be provided when running a job flow.
	 */
	public static function run_if($condition, $args = null)
	{
		if (!$args) $args = array();
		$args = is_array($args) ? $args : array($args);

        return self::script_runner('s3://us-east-1.elasticmapreduce/bootstrap-actions/run-if', $args);
	}

	/**
	 * Specify options to merge with Hadoop's default configuration.
	 *
	 * @param string $file (Required) The Hadoop configuration file to merge with. [Allowed values: <code>CFHadoopBootstrap::CONFIG_SITE</code>, <code>CFHadoopBootstrap::CONFIG_DEFAULT</code>, <code>CFHadoopBootstrap::CONFIG_CORE</code>, <code>CFHadoopBootstrap::CONFIG_HDFS</code>, <code>CFHadoopBootstrap::CONFIG_MAPREDUCE</code>]
	 * @param string|array $config (Required) This can either be an XML file in S3 (as <code>s3://bucket/path</code>), or an associative array of key-value pairs.
	 * @return array A configuration set to be provided when running a job flow.
	 */
	public static function configure($file, $config)
	{
		$args = array();
		$file_arg = '-' . $file;

		if (is_string($config))
		{
			$args[] = $file_arg;
			$args[] = $config;
		}
		elseif (is_array($config))
		{
			foreach ($config as $key => $value)
			{
				$args[] = $file_arg;
				$args[] = $key . '=' . $value;
			}
		}

        return self::script_runner('s3://us-east-1.elasticmapreduce/bootstrap-actions/configure-hadoop', $args);
	}

    /**
     * Create a new bootstrap action which lets you configure Hadoop's daemons. The options are written to
     * the <code>hadoop-user-env.sh</code> file.
     *
     * @param string $daemon_type (Required) The Hadoop daemon to configure.
	 * @param array $opt (Optional) An associative array of parameters that can have the following keys: <ul>
	 * 	<li><code>HeapSize</code> - <code>integer</code> - Optional - The requested heap size of the daemon, in megabytes.</li>
	 * 	<li><code>CLIOptions</code> - <code>string</code> - Optional - Additional Java command line arguments to pass to the daemon.</li>
	 * 	<li><code>Replace</code> - <code>boolean</code> - Optional - Whether or not the file should be replaced. A value of <code>true</code> will replace the existing configuration file. A value of <code>false</code> will append the options to the configuration file.</li></ul>
	 * @return array A configuration set to be provided when running a job flow.
     */
	public static function daemon($daemon_type, $opt = null)
	{
		if (!$opt) $opt = array();
		$args = array();

		foreach ($opt as $key => $value)
		{
			switch ($key)
			{
				case 'HeapSize':
					$args[] = '--' . $daemon_type . '-heap-size=' . $value;
					break;
				case 'CLIOptions':
					$args[] = '--' . $daemon_type . '-opts="' . $value . '"';
					break;
				case 'Replace':
					if ((is_string($value) && $value === 'true') || (is_bool($value) && $value === true))
					{
						$args[] = '--replace';
					}
					break;
			}
		}

        return self::script_runner('s3://us-east-1.elasticmapreduce/bootstrap-actions/configure-daemons', $args);
	}
}
