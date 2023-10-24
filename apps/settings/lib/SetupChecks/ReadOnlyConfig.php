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
