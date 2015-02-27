<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\Common\EventSubscriber;

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
		$sensitive = ($this->caseSensitiveLike) ? 'true' : 'false';
		$args->getConnection()->executeUpdate('PRAGMA case_sensitive_like = ' . $sensitive);
		$args->getConnection()->executeUpdate('PRAGMA journal_mode = ' . $this->journalMode);
	}

	public function getSubscribedEvents() {
		return array(Events::postConnect);
	}
}
