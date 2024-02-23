<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		if ($this->wasEmailTestSuccessful()) {
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
