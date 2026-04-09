<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming\Migration;

use Closure;
use OCA\Theming\AppInfo\Application;
use OCA\Theming\Jobs\RestoreBackgroundImageColor;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IMigrationStep;
use OCP\Migration\IOutput;

// This can only be executed once because `background_color` is again used with Nextcloud 30,
// so this part only works when updating -> Nextcloud 29 -> 30
class Version2006Date20240905111627 implements IMigrationStep {

	public function __construct(
		private IJobList $jobList,
		private IAppConfig $appConfig,
		private IDBConnection $connection,
	) {
	}

	public function name(): string {
		return 'Restore custom primary color';
	}

	public function description(): string {
		return 'Restore custom primary color after separating primary color from background color';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// nop
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		$this->restoreSystemColors($output);

		$userThemingEnabled = $this->appConfig->getValueBool('theming', 'disable-user-theming') === false;
		if ($userThemingEnabled) {
			$this->restoreUserColors($output);
		}

		return null;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$output->info('Initialize restoring of background colors for custom background images');
		// This is done in a background job as this can take a lot of time for large instances
		$this->jobList->add(RestoreBackgroundImageColor::class, ['stage' => RestoreBackgroundImageColor::STAGE_PREPARE]);
	}

	private function restoreSystemColors(IOutput $output): void {
		$defaultColor = $this->appConfig->getValueString(Application::APP_ID, 'color', '');
		if ($defaultColor === '') {
			$output->info('No custom system color configured - skipping');
		} else {
			// Restore legacy value into new field
			$this->appConfig->setValueString(Application::APP_ID, 'background_color', $defaultColor);
			$this->appConfig->setValueString(Application::APP_ID, 'primary_color', $defaultColor);
			// Delete legacy field
			$this->appConfig->deleteKey(Application::APP_ID, 'color');
			// give some feedback
			$output->info('Global primary color restored');
		}
	}

	private function restoreUserColors(IOutput $output): void {
		$output->info('Restoring user primary color');
		// For performance let the DB handle this
		$qb = $this->connection->getQueryBuilder();
		// Rename the `background_color` config to `primary_color` as this was the behavior on Nextcloud 29 and older
		// with Nextcloud 30 `background_color` is a new option to define the background color independent of the primary color.
		$qb->update('preferences')
			->set('configkey', $qb->createNamedParameter('primary_color'))
			->where($qb->expr()->eq('appid', $qb->createNamedParameter(Application::APP_ID)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('background_color')));

		try {
			$qb->executeStatement();
		} catch (\Exception) {
			$output->debug('Some users already configured the background color');
			$this->restoreUserColorsFallback($output);
		}

		$output->info('Primary color of users restored');
	}

	/**
	 * Similar to restoreUserColors but also works if some users already setup a new value.
	 * This is only called if the first approach fails as this takes much longer on the DB.
	 */
	private function restoreUserColorsFallback(IOutput $output): void {
		$qb = $this->connection->getQueryBuilder();
		$qb2 = $this->connection->getQueryBuilder();

		$qb2->select('userid')
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter(Application::APP_ID)))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('primary_color')));

		// MySQL does not update on select of the same table, so this is a workaround:
		if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_MYSQL) {
			$subquery = 'SELECT * from ( ' . $qb2->getSQL() . ' ) preferences_alias';
		} else {
			$subquery = $qb2->getSQL();
		}

		$qb->update('preferences')
			->set('configkey', $qb->createNamedParameter('primary_color'))
			->where($qb->expr()->eq('appid', $qb->createNamedParameter(Application::APP_ID)))
			->andWhere(
				$qb->expr()->eq('configkey', $qb->createNamedParameter('background_color')),
				$qb->expr()->notIn('userid', $qb->createFunction($subquery)),
			);

		$qb->executeStatement();
	}
}
