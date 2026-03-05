<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class DebugMode implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Debug mode');
	}

	public function getCategory(): string {
		return 'system';
	}

	public function run(): SetupResult {
		if ($this->config->getSystemValueBool('debug', false)) {
			return SetupResult::warning($this->l10n->t('This instance is running in debug mode. Only enable this for local development and not in production environments.'));
		} else {
			return SetupResult::success($this->l10n->t('Debug mode is disabled.'));
		}
	}
}
