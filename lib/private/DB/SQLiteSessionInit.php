<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

class SQLiteSessionInit implements EventSubscriber {
	/**
	 * Configure case-sensitive like for each connection
	 */
	public function __construct(
		private readonly bool $caseSensitiveLike,
		private readonly string $journalMode,
	) {
	}

	public function postConnect(ConnectionEventArgs $args): void {
		$sensitive = $this->caseSensitiveLike ? 'true' : 'false';
		$args->getConnection()->executeUpdate('PRAGMA case_sensitive_like = ' . $sensitive);
		$args->getConnection()->executeUpdate('PRAGMA journal_mode = ' . $this->journalMode);
		/** @var \Doctrine\DBAL\Driver\PDO\Connection $connection */
		$connection = $args->getConnection()->getWrappedConnection();
		$pdo = $connection->getWrappedConnection();
		if (PHP_VERSION_ID >= 80500 && method_exists($pdo, 'createFunction')) {
			$pdo->createFunction('md5', 'md5', 1);
		} else {
			$pdo->sqliteCreateFunction('md5', 'md5', 1);
		}
	}

	public function getSubscribedEvents(): array {
		return [Events::postConnect];
	}
}
