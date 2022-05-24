<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Log;

use Error;
use OCP\ILogger;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler {
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
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
		switch ($errno) {
			case E_USER_WARNING:
				return ILogger::WARN;

			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				return ILogger::DEBUG;

			case E_USER_NOTICE:
				return ILogger::INFO;

			case E_USER_ERROR:
			default:
				return ILogger::ERROR;
		}
	}
}
