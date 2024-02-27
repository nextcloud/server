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
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class CronInfo implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private IDateTimeFormatter $dateTimeFormatter,
	) {
	}

	public function getCategory(): string {
		return 'system';
	}

	public function getName(): string {
		return $this->l10n->t('Cron last run');
	}

	public function run(): SetupResult {
		$lastCronRun = (int)$this->config->getAppValue('core', 'lastcron', '0');
		$relativeTime = $this->dateTimeFormatter->formatTimeSpan($lastCronRun);

		if ((time() - $lastCronRun) > 3600) {
			return SetupResult::error(
				$this->l10n->t(
					'Last background job execution ran %s. Something seems wrong. {link}.',
					[$relativeTime]
				),
				descriptionParameters:[
					'link' => [
						'type' => 'highlight',
						'id' => 'backgroundjobs',
						'name' => 'Check the background job settings',
						'link' => $this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => 'server']) . '#backgroundjobs',
					],
				],
			);
		} else {
			return SetupResult::success(
				$this->l10n->t(
					'Last background job execution ran %s.',
					[$relativeTime]
				)
			);
		}
	}
}
