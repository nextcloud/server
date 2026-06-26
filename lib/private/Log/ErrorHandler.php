<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Log;

use Error;
use OCP\ILogger;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler {
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Remove password in URLs
	 */
	private static function removePassword(string $msg): string {
		return preg_replace('#//(.*):(.*)@#', '//xxx:xxx@', $msg);
	}

	/**
	 * Fatal errors handler
	 */
	public function onShutdown(): void {
		$error = error_get_last();
		if ($error) {
			$msg = $error['message'] . ' at ' . $error['file'] . '#' . $error['line'];
			$this->logger->critical(self::removePassword($msg), ['app' => 'PHP']);
		}
	}

	/**
	 * Uncaught exception handler
	 */
	public function onException(Throwable $exception): void {
		$class = get_class($exception);
		$msg = $exception->getMessage();
		$msg = "$class: $msg at " . $exception->getFile() . '#' . $exception->getLine();
		$this->logger->critical(self::removePassword($msg), ['app' => 'PHP']);
	}

	/**
	 * Recoverable errors handler
	 */
	public function onError(int $number, string $message, string $file, int $line): bool {
		if (!(error_reporting() & $number)) {
			return true;
		}
		$msg = $message . ' at ' . $file . '#' . $line;
		$e = new Error(self::removePassword($msg));
		$this->logger->log(self::errnoToLogLevel($number), $e->getMessage(), ['app' => 'PHP']);
		return true;
	}

	/**
	 * Recoverable handler which catch all errors, warnings and notices
	 */
	public function onAll(int $number, string $message, string $file, int $line): bool {
		$msg = $message . ' at ' . $file . '#' . $line;
		$e = new Error(self::removePassword($msg));
		$this->logger->log(self::errnoToLogLevel($number), $e->getMessage(), ['app' => 'PHP']);
		return true;
	}

	private static function errnoToLogLevel(int $errno): int {
		return match ($errno) {
			E_WARNING, E_USER_WARNING => ILogger::WARN,
			E_DEPRECATED, E_USER_DEPRECATED => ILogger::DEBUG,
			E_NOTICE, E_USER_NOTICE => ILogger::INFO,
			default => ILogger::ERROR,
		};
	}
}
