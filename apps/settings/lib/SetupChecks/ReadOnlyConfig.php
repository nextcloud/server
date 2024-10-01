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

class ReadOnlyConfig implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Configuration file access rights');
	}

	public function getCategory(): string {
		return 'config';
	}

	public function run(): SetupResult {
		if ($this->config->getSystemValueBool('config_is_read_only', false)) {
			return SetupResult::info($this->l10n->t('The read-only config has been enabled. This prevents setting some configurations via the web-interface. Furthermore, the file needs to be made writable manually for every update.'));
		} else {
			return SetupResult::success($this->l10n->t('Nextcloud configuration file is writable'));
		}
	}
}
