<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Migration;

use OCA\Theming\AppInfo\Application;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;

class SeparatePrimaryColorAndBackground implements \OCP\Migration\IRepairStep {

	public function __construct(
		private IConfig $config,
		private IDBConnection $connection,
	) {
	}

	public function getName() {
		return 'Restore custom primary color after separating primary color from background color';
	}

	public function run(IOutput $output) {
		$defaultColor = $this->config->getAppValue(Application::APP_ID, 'color', '');
		if ($defaultColor !== '') {
			// Restore legacy value into new field
			$this->config->setAppValue(Application::APP_ID, 'background_color', $defaultColor);
			$this->config->setAppValue(Application::APP_ID, 'primary_color', $defaultColor);
			// Delete legacy field
			$this->config->deleteAppValue(Application::APP_ID, 'color');
			// give some feedback
			$output->info('Global primary color restored');
		}

		// This can only be executed once because `background_color` is again used with Nextcloud 30,
		// so this part only works when updating -> Nextcloud 29 -> 30
		$migrated = $this->config->getAppValue('theming', 'nextcloud_30_migration', 'false') === 'true';
		if ($migrated) {
			return;
		}

		$userThemingEnabled = $this->config->getAppValue('theming', 'disable-user-theming', 'no') !== 'yes';
		if ($userThemingEnabled) {
			$output->info('Restoring user primary color');
			// For performance let the DB handle this
			$qb = $this->connection->getQueryBuilder();
			// Rename the `background_color` config to `primary_color` as this was the behavior on Nextcloud 29 and older
			// with Nextcloud 30 `background_color` is a new option to define the background color independent of the primary color.
			$qb->update('preferences')
				->set('configkey', $qb->createNamedParameter('primary_color'))
				->where($qb->expr()->eq('appid', $qb->createNamedParameter(Application::APP_ID)))
				->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('background_color')));
			$qb->executeStatement();
			$output->info('Primary color of users restored');
		}
		$this->config->setAppValue('theming', 'nextcloud_30_migration', 'true');
	}
}
