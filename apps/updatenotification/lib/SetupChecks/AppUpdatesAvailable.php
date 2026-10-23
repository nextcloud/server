<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UpdateNotification\SetupChecks;

use OC\Installer;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class AppUpdatesAvailable implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private IAppManager $appManager,
		private Installer $installer,
	) {
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('App updates');
	}

	#[\Override]
	public function getCategory(): string {
		return 'system';
	}

	#[\Override]
	public function run(): SetupResult {
		if (!$this->config->getSystemValueBool('has_internet_connection', true)) {
			return SetupResult::success();
		}

		$outdated = array_values(array_filter(
			$this->appManager->getEnabledApps(),
			fn (string $app): bool => $this->installer->isUpdateAvailable($app) !== false,
		));

		if ($outdated === []) {
			return SetupResult::success($this->l10n->t('All apps are up to date.'));
		}

		return SetupResult::warning(
			$this->l10n->n(
				'An update is available for the following app: %s',
				'Updates are available for the following apps: %s',
				count($outdated),
				[implode(', ', $outdated)],
			),
			$this->urlGenerator->linkToRoute('appstore.Page.viewApps', ['category' => 'updates']),
		);
	}
}
