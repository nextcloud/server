<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Log;

use OC\Log;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Log\IDataLogger;
use OCP\LogLevel;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;
use function array_key_exists;
use function array_merge;

final class PsrLoggerAdapter implements LoggerInterface, IDataLogger {
	public function __construct(
		private Log $logger,
	) {
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
	 * @param  $message
	 * @param mixed[] $context
	 */
	public function emergency($message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				$context,
				[
					'message' => (string)$message,
					'level' => LogLevel::EMERGENCY,
				],
			));
		} else {
			$this->logger->emergency((string)$message, $context);
		}
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param  $message
	 * @param mixed[] $context
	 */
	public function alert($message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				$context,
				[
					'message' => (string)$message,
					'level' => LogLevel::ALERT,
				],
			));
		} else {
			$this->logger->alert((string)$message, $context);
		}
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param  $message
	 * @param mixed[] $context
	 */
	public function critical($message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				$context,
				[
					'message' => (string)$message,
					'level' => LogLevel::CRITICAL,
				],
			));
		} else {
			$this->logger->critical((string)$message, $context);
		}
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param  $message
	 * @param mixed[] $context
	 */
	public function error($message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				$context,
				[
					'message' => (string)$message,
					'level' => LogLevel::ERROR,
				],
			));
		} else {
			$this->logger->error((string)$message, $context);
		}
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param  $message
	 * @param mixed[] $context
	 */
	public function warning($message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				$context,
				[
					'message' => (string)$message,
					'level' => LogLevel::WARNING,
				],
			));
		} else {
			$this->logger->warning((string)$message, $context);
		}
	}

	/**
	 * Normal but significant events.
	 *
	 * @param  $message
	 * @param mixed[] $context
	 */
	public function notice($message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				$context,
				[
					'message' => (string)$message,
					'level' => LogLevel::NOTICE,
				],
			));
		} else {
			$this->logger->notice((string)$message, $context);
		}
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param  $message
	 * @param mixed[] $context
	 */
	public function info($message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				$context,
				[
					'message' => (string)$message,
					'level' => LogLevel::INFO,
				],
			));
		} else {
			$this->logger->info((string)$message, $context);
		}
	}

	/**
	 * Detailed debug information.
	 *
	 * @param  $message
	 * @param mixed[] $context
	 */
	public function debug($message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				$context,
				[
					'message' => (string)$message,
					'level' => LogLevel::DEBUG,
				],
			));
		} else {
			$this->logger->debug((string)$message, $context);
		}
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param  $message
	 * @param mixed[] $context
	 *
	 * @throws InvalidArgumentException
	 */
	public function log($level, $message, array $context = []): void {
		if (is_int($level)) {
			$level = LogLevel::tryFrom($level);
		} elseif (is_string($level)) {
			$level = LogLevel::fromPsrLogLevel($level);
		}
		if (!($level instanceof LogLevel)) {
			throw new InvalidArgumentException('Nextcloud allows only PSR-3 and integer log levels');
		}

		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				$context,
				[
					'message' => (string)$message,
					'level' => $level,
				],
			));
		} else {
			$this->logger->log($level, (string)$message, $context);
		}
	}

	public function logData(string $message, array $data, array $context = []): void {
		$this->logger->logData($message, $data, $context);
	}
}
