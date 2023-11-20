<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Morris Jobke <hey@morrisjobke.de>
 * @copyright Copyright (c) 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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
		return $this->l10n->t('Old user imported certificates');
	}

	public function run(): SetupResult {
		// all fine if neither "not-run-yet" nor a result
		if ($this->configValue === '') {
			return SetupResult::success();
		}
		if ($this->configValue === 'not-run-yet') {
			return SetupResult::info($this->l10n->t('A background job is pending that checks for user imported SSL certificates. Please check back later.'));
		}
		return SetupResult::error($this->l10n->t('There are some user imported SSL certificates present, that are not used anymore with Nextcloud 21. They can be imported on the command line via "occ security:certificates:import" command. Their paths inside the data directory are shown below.'));
	}
}
