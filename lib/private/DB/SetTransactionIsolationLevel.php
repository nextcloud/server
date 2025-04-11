<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\TransactionIsolationLevel;

class SetTransactionIsolationLevel implements EventSubscriber {
	/**
	 * @param ConnectionEventArgs $args
	 * @return void
	 */
	public function postConnect(ConnectionEventArgs $args) {
		$connection = $args->getConnection();
		if ($connection instanceof PrimaryReadReplicaConnection && $connection->isConnectedToPrimary()) {
			$connection->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);
			if ($connection->getDatabasePlatform() instanceof MySQLPlatform) {
				$connection->executeStatement('SET SESSION AUTOCOMMIT=1');
			}
		}
	}

	public function getSubscribedEvents() {
		return [Events::postConnect];
	}
}
