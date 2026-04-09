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

class CronErrors implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
	) {
	}

	public function getCategory(): string {
		return 'system';
	}

	public function getName(): string {
		return $this->l10n->t('Cron errors');
	}

	public function run(): SetupResult {
		$errors = json_decode($this->config->getAppValue('core', 'cronErrors', ''), true);
		if (is_array($errors) && count($errors) > 0) {
			return SetupResult::error(
				$this->l10n->t(
					"It was not possible to execute the cron job via CLI. The following technical errors have appeared:\n%s",
					implode("\n", array_map(fn (array $error) => '- ' . $error['error'] . ' ' . $error['hint'], $errors))
				)
			);
		} else {
			return SetupResult::success($this->l10n->t('The last cron job ran without errors.'));
		}
	}
}
