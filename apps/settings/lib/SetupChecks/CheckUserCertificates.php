<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class CheckUserCertificates implements ISetupCheck {
	private string $configValue;

	public function __construct(
		private IL10N $l10n,
		IConfig $config,
	) {
		$this->configValue = $config->getAppValue('files_external', 'user_certificate_scan', '');
	}

	public function getCategory(): string {
		return 'security';
	}

	public function getName(): string {
		return $this->l10n->t('Old administration imported certificates');
	}

	public function run(): SetupResult {
		// all fine if neither "not-run-yet" nor a result
		if ($this->configValue === '') {
			return SetupResult::success();
		}
		if ($this->configValue === 'not-run-yet') {
			return SetupResult::info($this->l10n->t('A background job is pending that checks for administration imported SSL certificates. Please check back later.'));
		}
		return SetupResult::error($this->l10n->t('There are some administration imported SSL certificates present, that are not used anymore with Nextcloud 21. They can be imported on the command line via "occ security:certificates:import" command. Their paths inside the data directory are shown below.'));
	}
}
