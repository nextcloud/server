<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class LoggingLevel implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Logging level');
	}

	public function getCategory(): string {
		return 'system';
	}

	public function run(): SetupResult {
		$configLogLevel = $this->config->getSystemValue('loglevel', ILogger::WARN);
		if (!is_int($configLogLevel)
			|| $configLogLevel < ILogger::DEBUG
			|| $configLogLevel > ILogger::FATAL
		) {
			return SetupResult::error(
				$this->l10n->t('The %1$s configuration option must be a valid integer value.', ['`loglevel`']),
				$this->urlGenerator->linkToDocs('admin-logging'),
			);
		}

		if ($configLogLevel === ILogger::DEBUG) {
			return SetupResult::warning(
				$this->l10n->t('The logging level is set to debug level. Use debug level only when you have a problem to diagnose, and then reset your log level to a less-verbose level as it outputs a lot of information, and can affect your server performance.'),
				$this->urlGenerator->linkToDocs('admin-logging'),
			);
		}

		return SetupResult::success($this->l10n->t('Logging level configured correctly.'));
	}
}
