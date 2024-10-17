<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Migration;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Share\IShare;

class SetAcceptedStatus implements IRepairStep {

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
	public function getName(): string {
		return 'Set existing shares as accepted';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output): void {
		if (!$this->shouldRun()) {
			return;
		}

		$query = $this->connection->getQueryBuilder();
		$query
			->update('share')
			->set('accepted', $query->createNamedParameter(IShare::STATUS_ACCEPTED))
			->where($query->expr()->in('share_type', $query->createNamedParameter([IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_USERGROUP], IQueryBuilder::PARAM_INT_ARRAY)));
		$query->executeStatement();
	}

	protected function shouldRun() {
		$appVersion = $this->config->getAppValue('files_sharing', 'installed_version', '0.0.0');
		return version_compare($appVersion, '1.10.1', '<');
	}
}
