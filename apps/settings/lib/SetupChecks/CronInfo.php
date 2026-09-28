<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IAppConfig;
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
		private IAppConfig $appConfig,
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
		$lastCronRun = $this->appConfig->getValueInt('core', 'lastcron', 0);
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
