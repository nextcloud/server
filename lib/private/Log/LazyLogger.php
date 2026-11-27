<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Log;

use OCP\IConfig;
use OCP\ILogger;
use OCP\Log\ILazyLogger;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 * @since 39.0.0
 */
class LazyLogger implements ILazyLogger {
	public ?LazyLoggerInterface $debug = null;
	public ?LazyLoggerInterface $info = null;
	public ?LazyLoggerInterface $warning = null;
	public ?LazyLoggerInterface $error = null;
	public ?LazyLoggerInterface $fatal = null;

	public function __construct(
		LoggerInterface $logger,
		IConfig $config,
	) {
		$logLevel = $config->getSystemValueInt('loglevel', ILogger::WARN);
		if ($logLevel === ILogger::DEBUG) {
			$this->debug = new LazyLoggerInterface($logger, ILogger::DEBUG);
		}
		if ($logLevel <= ILogger::INFO) {
			$this->info = new LazyLoggerInterface($logger, ILogger::INFO);
		}
		if ($logLevel <= ILogger::WARN) {
			$this->warning = new LazyLoggerInterface($logger, ILogger::WARN);
		}
		if ($logLevel <= ILogger::ERROR) {
			$this->error = new LazyLoggerInterface($logger, ILogger::ERROR);
		}
		$this->fatal = new LazyLoggerInterface($logger, ILogger::FATAL);
	}
}
