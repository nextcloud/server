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
 * Class OwncloudGuestShareType
 *
 * @package OCA\Files_Sharing\Migration
 */
class OwncloudGuestShareType implements IRepairStep {

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
		return 'Fix the share type of guest shares when migrating from ownCloud';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		if (!$this->shouldRun()) {
			return;
		}

		$query = $this->connection->getQueryBuilder();
		$query->update('share')
			->set('share_type', $query->createNamedParameter(IShare::TYPE_GUEST))
			->where($query->expr()->eq('share_type', $query->createNamedParameter(IShare::TYPE_EMAIL)));
		$query->execute();
	}

	protected function shouldRun() {
		$appVersion = $this->config->getAppValue('files_sharing', 'installed_version', '0.0.0');
		return $appVersion === '0.10.0' ||
			$this->config->getAppValue('core', 'vendor', '') === 'owncloud';
	}
}
