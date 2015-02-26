<?php
/**
 * Copyright (c) 2014 Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

/**
 * Interface ILogger
 * @package OCP
 *
 * This logger interface follows the design guidelines of PSR-3
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#3-psrlogloggerinterface
 */
interface ILogger {
	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	function emergency($message, array $context = array());

	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	function alert($message, array $context = array());

	/**
	 * Critical conditions.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	function critical($message, array $context = array());

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	function error($message, array $context = array());

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	function warning($message, array $context = array());

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	function notice($message, array $context = array());

	/**
	 * Interesting events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	function info($message, array $context = array());

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	function debug($message, array $context = array());

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return mixed
	 */
	function log($level, $message, array $context = array());
}
