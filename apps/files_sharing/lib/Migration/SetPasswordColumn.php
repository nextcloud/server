<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Migration;

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Share\IShare;

/**
 * Class SetPasswordColumn
 *
 * @package OCA\Files_Sharing\Migration
 */
class SetPasswordColumn implements IRepairStep {

	public function __construct(
		private IDBConnection $connection,
		private IConfig $config,
	) {
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Copy the share password into the dedicated column';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		if (!$this->shouldRun()) {
			return;
		}

		$query = $this->connection->getQueryBuilder();
		$query
			->update('share')
			->set('password', 'share_with')
			->where($query->expr()->eq('share_type', $query->createNamedParameter(IShare::TYPE_LINK)))
			->andWhere($query->expr()->isNotNull('share_with'));
		$result = $query->execute();

		if ($result === 0) {
			// No link updated, no need to run the second query
			return;
		}

		$clearQuery = $this->connection->getQueryBuilder();
		$clearQuery
			->update('share')
			->set('share_with', $clearQuery->createNamedParameter(null))
			->where($clearQuery->expr()->eq('share_type', $clearQuery->createNamedParameter(IShare::TYPE_LINK)));

		$clearQuery->execute();
	}

	protected function shouldRun() {
		$appVersion = $this->config->getAppValue('files_sharing', 'installed_version', '0.0.0');
		return version_compare($appVersion, '1.4.0', '<');
	}
}
