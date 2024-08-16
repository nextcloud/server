<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Log;

use OC\Log;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ILogger;
use OCP\Log\IDataLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use function array_key_exists;
use function array_merge;

final class PsrLoggerAdapter implements LoggerInterface, IDataLogger {
	public function __construct(
		private Log $logger,
	) {
	}

	public static function logLevelToInt(string $level): int {
		return match ($level) {
			LogLevel::ALERT => ILogger::ERROR,
			LogLevel::CRITICAL => ILogger::ERROR,
			LogLevel::DEBUG => ILogger::DEBUG,
			LogLevel::EMERGENCY => ILogger::FATAL,
			LogLevel::ERROR => ILogger::ERROR,
			LogLevel::INFO => ILogger::INFO,
			LogLevel::NOTICE => ILogger::INFO,
			LogLevel::WARNING => ILogger::WARN,
			default => throw new InvalidArgumentException('Unsupported custom log level'),
		};
	}

	public function setEventDispatcher(IEventDispatcher $eventDispatcher): void {
		$this->logger->setEventDispatcher($eventDispatcher);
	}

	private function containsThrowable(array $context): bool {
		return array_key_exists('exception', $context) && $context['exception'] instanceof Throwable;
	}

	/**
	 * System is unusable.
	 *
	 * @param $message
	 * @param mixed[] $context
	 */
	public function emergency($message, array $context = []): void {
		$this->log(LogLevel::EMERGENCY, (string)$message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param $message
	 * @param mixed[] $context
	 */
	public function alert($message, array $context = []): void {
		$this->log(LogLevel::ALERT, (string)$message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param $message
	 * @param mixed[] $context
	 */
	public function critical($message, array $context = []): void {
		$this->log(LogLevel::CRITICAL, (string)$message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param $message
	 * @param mixed[] $context
	 */
	public function error($message, array $context = []): void {
		$this->log(LogLevel::ERROR, (string)$message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param $message
	 * @param mixed[] $context
	 */
	public function warning($message, array $context = []): void {
		$this->log(LogLevel::WARNING, (string)$message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param $message
	 * @param mixed[] $context
	 */
	public function notice($message, array $context = []): void {
		$this->log(LogLevel::NOTICE, (string)$message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param $message
	 * @param mixed[] $context
	 */
	public function info($message, array $context = []): void {
		$this->log(LogLevel::INFO, (string)$message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param $message
	 * @param mixed[] $context
	 */
	public function debug($message, array $context = []): void {
		$this->log(LogLevel::DEBUG, (string)$message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param $message
	 * @param mixed[] $context
	 *
	 * @throws InvalidArgumentException
	 */
	public function log($level, $message, array $context = []): void {
		if (is_string($level)) {
			$level = self::logLevelToInt($level);
		}
		if (isset($context['level']) && is_string($context['level'])) {
			$context['level'] = self::logLevelToInt($context['level']);
		}
		if (!is_int($level) || $level < ILogger::DEBUG || $level > ILogger::FATAL) {
			throw new InvalidArgumentException('Unsupported custom log level');
		}
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => (string)$message,
					'level' => $level,
				],
				$context
			));
		} else {
			$this->logger->log($level, (string)$message, $context);
		}
	}

	public function logData(string $message, array $data, array $context = []): void {
		$this->logger->logData($message, $data, $context);
	}
}
