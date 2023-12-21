<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

class MaintenanceWindowStart implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IConfig $config,
	) {
	}

	public function getCategory(): string {
		return 'system';
	}

	public function getName(): string {
		return $this->l10n->t('Maintenance window start');
	}

	public function run(): SetupResult {
		$configValue = $this->config->getSystemValue('maintenance_window_start', null);
		if ($configValue === null) {
			return SetupResult::warning(
				$this->l10n->t('Server has no maintenance window start time configured. This means resource intensive daily background jobs will also be executed during your main usage time. We recommend to set it to a time of low usage, so users are less impacted by the load caused from these heavy tasks.'),
				$this->urlGenerator->linkToDocs('admin-background-jobs')
			);
		}

		$startValue = (int) $configValue;
		$endValue = ($startValue + 6) % 24;
		return SetupResult::success(
			str_replace(
				['{start}', '{end}'],
				[$startValue, $endValue],
				$this->l10n->t('Maintenance window to execute heavy background jobs is between {start}:00 UTC and {end}:00 UTC')
			)
		);
	}
}
