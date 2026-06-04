<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Diagnostics;

use OCP\ILogger;
use Psr\Log\LoggerInterface;
use function microtime;

trait TLogSlowOperation {

	/**
	 * @template R
	 * @param LoggerInterface $logger
	 * @param string $operation
	 * @param callable $fn
	 * @psalm-param callable(): R $fn
	 *
	 * @return mixed
	 */
	public function monitorAndLog(LoggerInterface $logger, string $operation, callable $fn): mixed {
		$timeBefore = microtime(true);
		$result = $fn();
		$timeAfter = microtime(true);
		$timeSpent = $timeAfter - $timeBefore;
		if ($timeSpent > 0.1) {
			$logLevel = match (true) {
				$timeSpent > 25 => ILogger::ERROR,
				$timeSpent > 10 => ILogger::WARN,
				$timeSpent > 0.5 => ILogger::INFO,
				default => ILogger::DEBUG,
			};
			$logger->log(
				$logLevel,
				"Slow $operation detected",
				[
					'timeSpent' => $timeSpent,
				],
			);
		}
		return $result;
	}

}
