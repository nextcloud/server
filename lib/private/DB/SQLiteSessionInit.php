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
	 * @var bool
	 */
	private $caseSensitiveLike;

	/**
	 * @var string
	 */
	private $journalMode;

	/**
	 * Configure case sensitive like for each connection
	 *
	 * @param bool $caseSensitiveLike
	 * @param string $journalMode
	 */
	public function __construct($caseSensitiveLike, $journalMode) {
		$this->caseSensitiveLike = $caseSensitiveLike;
		$this->journalMode = $journalMode;
	}

	/**
	 * @param ConnectionEventArgs $args
	 * @return void
	 */
	public function postConnect(ConnectionEventArgs $args) {
		$sensitive = $this->caseSensitiveLike ? 'true' : 'false';
		$args->getConnection()->executeUpdate('PRAGMA case_sensitive_like = ' . $sensitive);
		$args->getConnection()->executeUpdate('PRAGMA journal_mode = ' . $this->journalMode);
		/** @var \Doctrine\DBAL\Driver\PDO\Connection $connection */
		$connection = $args->getConnection()->getWrappedConnection();
		$pdo = $connection->getWrappedConnection();
		$pdo->sqliteCreateFunction('md5', 'md5', 1);
	}

	public function getSubscribedEvents() {
		return [Events::postConnect];
	}
}
