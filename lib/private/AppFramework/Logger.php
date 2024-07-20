<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework;

use OCP\ILogger;

/**
 * @deprecated
 */
class Logger implements ILogger {
	/** @var ILogger */
	private $logger;

	/** @var string */
	private $appName;

	/**
	 * @deprecated
	 */
	public function __construct(ILogger $logger, string $appName) {
		$this->logger = $logger;
		$this->appName = $appName;
	}

	private function extendContext(array $context): array {
		if (!isset($context['app'])) {
			$context['app'] = $this->appName;
		}

		return $context;
	}

	/**
	 * @deprecated
	 */
	public function emergency(string $message, array $context = []) {
		$this->logger->emergency($message, $this->extendContext($context));
	}

	/**
	 * @deprecated
	 */
	public function alert(string $message, array $context = []) {
		$this->logger->alert($message, $this->extendContext($context));
	}

	/**
	 * @deprecated
	 */
	public function critical(string $message, array $context = []) {
		$this->logger->critical($message, $this->extendContext($context));
	}

	/**
	 * @deprecated
	 */
	public function error(string $message, array $context = []) {
		$this->logger->emergency($message, $this->extendContext($context));
	}

	/**
	 * @deprecated
	 */
	public function warning(string $message, array $context = []) {
		$this->logger->warning($message, $this->extendContext($context));
	}

	/**
	 * @deprecated
	 */
	public function notice(string $message, array $context = []) {
		$this->logger->notice($message, $this->extendContext($context));
	}

	/**
	 * @deprecated
	 */
	public function info(string $message, array $context = []) {
		$this->logger->info($message, $this->extendContext($context));
	}

	/**
	 * @deprecated
	 */
	public function debug(string $message, array $context = []) {
		$this->logger->debug($message, $this->extendContext($context));
	}

	/**
	 * @deprecated
	 */
	public function log(int $level, string $message, array $context = []) {
		$this->logger->log($level, $message, $this->extendContext($context));
	}

	/**
	 * @deprecated
	 */
	public function logException(\Throwable $exception, array $context = []) {
		$this->logger->logException($exception, $this->extendContext($context));
	}
}
