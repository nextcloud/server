<?php
/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;

use Doctrine\DBAL\Query\QueryBuilder;
use OC\Hooks\BasicEmitter;

class FillETags extends BasicEmitter implements \OC\RepairStep {

	/** @var \OC\DB\Connection */
	protected $connection;

	/**
	 * @param \OC\DB\Connection $connection
	 */
	public function __construct($connection) {
		$this->connection = $connection;
	}

	public function getName() {
		return 'Generate ETags for file where no ETag is present.';
	}

	public function run() {
		$qb = $this->connection->createQueryBuilder();
		$qb->update('`*PREFIX*filecache`')
			->set('`etag`', $qb->expr()->literal('xxx'))
			->where($qb->expr()->eq('`etag`', $qb->expr()->literal('')))
			->orWhere($qb->expr()->isNull('`etag`'));

		$result = $qb->execute();
		$this->emit('\OC\Repair', 'info', array("ETags have been fixed for $result files/folders."));
	}
}

