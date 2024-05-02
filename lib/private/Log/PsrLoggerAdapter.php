<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0-or-later
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
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ILogger;
use OCP\Log\IDataLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Stringable;
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
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 */
	public function emergency(string|Stringable $message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => (string)$message,
					'level' => ILogger::FATAL,
				],
				$context
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
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 */
	public function alert(string|Stringable $message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => (string)$message,
					'level' => ILogger::ERROR,
				],
				$context
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
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 */
	public function critical(string|Stringable $message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => (string)$message,
					'level' => ILogger::ERROR,
				],
				$context
			));
		} else {
			$this->logger->critical((string)$message, $context);
		}
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 */
	public function error(string|Stringable $message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => (string)$message,
					'level' => ILogger::ERROR,
				],
				$context
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
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 */
	public function warning(string|Stringable $message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => (string)$message,
					'level' => ILogger::WARN,
				],
				$context
			));
		} else {
			$this->logger->warning((string)$message, $context);
		}
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 */
	public function notice(string|Stringable $message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => (string)$message,
					'level' => ILogger::INFO,
				],
				$context
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
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 */
	public function info(string|Stringable $message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => (string)$message,
					'level' => ILogger::INFO,
				],
				$context
			));
		} else {
			$this->logger->info((string)$message, $context);
		}
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 */
	public function debug(string|Stringable $message, array $context = []): void {
		if ($this->containsThrowable($context)) {
			$this->logger->logException($context['exception'], array_merge(
				[
					'message' => (string)$message,
					'level' => ILogger::DEBUG,
				],
				$context
			));
		} else {
			$this->logger->debug((string)$message, $context);
		}
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 *
	 * @throws InvalidArgumentException
	 */
	public function log($level, string|Stringable $message, array $context = []): void {
		if (!is_int($level) || $level < ILogger::DEBUG || $level > ILogger::FATAL) {
			throw new InvalidArgumentException('Nextcloud allows only integer log levels');
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
