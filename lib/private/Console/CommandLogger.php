<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Console;

use OCP\ILogger;
use OCP\Util;
use Symfony\Component\Console\Output\OutputInterface;

class CommandLogger implements ILogger {

	/** @var OutputInterface */
	private $output;

	/**
	 * CommandLogger constructor.
	 *
	 * @param OutputInterface $output
	 */
	public function __construct(OutputInterface $output) {
		$this->output = $output;
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function emergency($message, array $context = array()) {
		$this->log(Util::FATAL, $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function alert($message, array $context = array()) {
		$this->log(Util::ERROR, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function critical($message, array $context = array()) {
		$this->log(Util::ERROR, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function error($message, array $context = array()) {
		$this->log(Util::ERROR, $message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function warning($message, array $context = array()) {
		$this->log(Util::WARN, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function notice($message, array $context = array()) {
		$this->log(Util::INFO, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function info($message, array $context = array()) {
		$this->log(Util::ERROR, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 * @since 7.0.0
	 */
	public function debug($message, array $context = array()) {
		$this->log(Util::DEBUG, $message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return mixed
	 * @since 7.0.0
	 */
	public function log($level, $message, array $context = array()) {
		$minLevel = Util::INFO;
		$verbosity = $this->output->getVerbosity();
		if ($verbosity === OutputInterface::VERBOSITY_DEBUG) {
			$minLevel = Util::DEBUG;
		}
		if ($verbosity === OutputInterface::VERBOSITY_QUIET) {
			$minLevel = Util::ERROR;
		}
		if ($level < $minLevel) {
			return;
		}

		// interpolate $message as defined in PSR-3
		$replace = array();
		foreach ($context as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}

		// interpolate replacement values into the message and return
		$message = strtr($message, $replace);
		$style = ($level > 2) ?'error' : 'info';

		$this->output->writeln("<$style>$message</$style>");
	}

	/**
	 * Logs an exception very detailed
	 * An additional message can we written to the log by adding it to the
	 * context.
	 *
	 * <code>
	 * $logger->logException($ex, [
	 *     'message' => 'Exception during cron job execution'
	 * ]);
	 * </code>
	 *
	 * @param \Exception | \Throwable $exception
	 * @param array $context
	 * @return void
	 * @since 8.2.0
	 */
	public function logException($exception, array $context = array()) {
		$exception = array(
			'Exception' => get_class($exception),
			'Message' => $exception->getMessage(),
			'Code' => $exception->getCode(),
			'Trace' => $exception->getTraceAsString(),
			'File' => $exception->getFile(),
			'Line' => $exception->getLine(),
		);
		$exception['Trace'] = preg_replace('!(login|checkPassword|updatePrivateKeyPassword)\(.*\)!', '$1(*** username and password replaced ***)', $exception['Trace']);
		$msg = isset($context['message']) ? $context['message'] : 'Exception';
		$msg .= ': ' . json_encode($exception);
		$this->error($msg, $context);
	}
}
