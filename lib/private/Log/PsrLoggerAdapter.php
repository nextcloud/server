<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Log;

use OC\Log;
use OCP\ILogger;
use OCP\Log\IDataLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;
use function array_key_exists;
use function array_merge;

final class PsrLoggerAdapter implements LoggerInterface, IDataLogger {
	/** @var Log */
	private $logger;

	public function __construct(Log $logger) {
		$this->logger = $logger;
	}

	private function containsThrowable(array $context): bool {
		return array_key_exists('exception', $context) && $context['exception'] instanceof Throwable;
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function emergency($message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => $message,
					'level' => ILogger::FATAL,
				],
				$context
			));
		} else {
			$this->logger->emergency($message, $context);
		}
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function alert($message, array $context = []) {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => $message,
					'level' => ILogger::ERROR,
				],
				$context
			));
		} else {
			$this->logger->alert($message, $context);
		}
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function critical($message, array $context = []) {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => $message,
					'level' => ILogger::ERROR,
				],
				$context
			));
		} else {
			$this->logger->critical($message, $context);
		}
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function error($message, array $context = []) {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => $message,
					'level' => ILogger::ERROR,
				],
				$context
			));
		} else {
			$this->logger->error($message, $context);
		}
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function warning($message, array $context = []) {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => $message,
					'level' => ILogger::WARN,
				],
				$context
			));
		} else {
			$this->logger->warning($message, $context);
		}
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function notice($message, array $context = []) {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => $message,
					'level' => ILogger::INFO,
				],
				$context
			));
		} else {
			$this->logger->notice($message, $context);
		}
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function info($message, array $context = []) {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => $message,
					'level' => ILogger::INFO,
				],
				$context
			));
		} else {
			$this->logger->info($message, $context);
		}
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function debug($message, array $context = []) {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => $message,
					'level' => ILogger::DEBUG,
				],
				$context
			));
		} else {
			$this->logger->debug($message, $context);
		}
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException
	 */
	public function log($level, $message, array $context = []) {
		if (!is_int($level) || $level < ILogger::DEBUG || $level > ILogger::FATAL) {
			throw new InvalidArgumentException('Nextcloud allows only integer log levels');
		}
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => $message,
					'level' => $level,
				],
				$context
			));
		} else {
			$this->logger->log($level, $message, $context);
		}
	}

	public function logData(string $message, array $data, array $context = []): void {
		$this->logger->logData($message, $data, $context);
	}
}
