<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Migration;

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class RmRefreshTime
 *
 * this can be removed with Nextcloud 21
 *
 * @package OCA\User_LDAP\Migration
 */
class RemoveRefreshTime implements IRepairStep {

	public function __construct(
		private IDBConnection $dbc,
		private IConfig $config,
	) {
	}

	public function getName() {
		return 'Remove deprecated refresh time markers for LDAP user records';
	}

	public function run(IOutput $output) {
		$this->config->deleteAppValue('user_ldap', 'updateAttributesInterval');

		$qb = $this->dbc->getQueryBuilder();
		$qb->delete('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter('user_ldap')))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('lastFeatureRefresh')))
			->executeStatement();
	}
}
