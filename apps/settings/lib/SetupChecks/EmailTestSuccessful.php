<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class EmailTestSuccessful implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Email test');
	}

	public function getCategory(): string {
		return 'config';
	}

	protected function wasEmailTestSuccessful(): bool {
		// Handle the case that the configuration was set before the check was introduced or it was only set via command line and not from the UI
		if ($this->config->getAppValue('core', 'emailTestSuccessful', '') === '' && $this->config->getSystemValue('mail_domain', '') === '') {
			return false;
		}

		// The mail test was unsuccessful or the config was changed using the UI without verifying with a testmail, hence return false
		if ($this->config->getAppValue('core', 'emailTestSuccessful', '') === '0') {
			return false;
		}

		return true;
	}

	public function run(): SetupResult {
		if ($this->config->getSystemValueString('mail_smtpmode', 'smtp') === 'null') {
			return SetupResult::success($this->l10n->t('Mail delivery is disabled by instance config "%s".', ['mail_smtpmode']));
		} elseif ($this->wasEmailTestSuccessful()) {
			return SetupResult::success($this->l10n->t('Email test was successfully sent'));
		} else {
			// If setup check could link to settings pages, this one should link to OC.generateUrl('/settings/admin')
			return SetupResult::info(
				$this->l10n->t('You have not set or verified your email server configuration, yet. Please head over to the "Basic settings" in order to set them. Afterwards, use the "Send email" button below the form to verify your settings.'),
				$this->urlGenerator->linkToDocs('admin-email')
			);
		}
	}
}
