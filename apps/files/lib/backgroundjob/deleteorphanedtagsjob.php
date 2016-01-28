<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files\BackgroundJob;

use OC\BackgroundJob\TimedJob;

/**
 * Delete all share entries that have no matching entries in the file cache table.
 */
class DeleteOrphanedTagsJob extends TimedJob {

	/** @var \OCP\IDBConnection */
	protected $connection;

	/** @var \OCP\ILogger */
	protected $logger;

	/**
	 * Default interval in minutes
	 *
	 * @var int $defaultIntervalMin
	 **/
	protected $defaultIntervalMin = 60;

	/**
	 * sets the correct interval for this timed job
	 */
	public function __construct() {
		$this->interval = $this->defaultIntervalMin * 60;
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->logger = \OC::$server->getLogger();
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 */
	public function run($argument) {
		$this->cleanSystemTags();
		$this->cleanUserTags();
	}

	/**
	 * Deleting orphaned system tag mappings
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanSystemTags() {
		$subQuery = $this->connection->getQueryBuilder();
		$subQuery->select($subQuery->expr()->literal('1'))
			->from('filecache', 'f')
			->where($subQuery->expr()->eq('objectid', 'f.fileid'));

		$query = $this->connection->getQueryBuilder();
		$deletedEntries = $query->delete('systemtag_object_mapping')
			->where($query->expr()->eq('objecttype', $query->expr()->literal('files')))
			->andWhere($query->expr()->isNull($query->createFunction('(' . $subQuery->getSql() . ')')))
			->execute();

		$this->logger->debug("$deletedEntries orphaned system tag relations deleted", ['app' => 'DeleteOrphanedTagsJob']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned user tag mappings
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanUserTags() {
		$subQuery = $this->connection->getQueryBuilder();
		$subQuery->select($subQuery->expr()->literal('1'))
			->from('filecache', 'f')
			->where($subQuery->expr()->eq('objid', 'f.fileid'));

		$query = $this->connection->getQueryBuilder();
		$deletedEntries = $query->delete('vcategory_to_object')
			->where($query->expr()->eq('type', $query->expr()->literal('files')))
			->andWhere($query->expr()->isNull($query->createFunction('(' . $subQuery->getSql() . ')')))
			->execute();

		$this->logger->debug("$deletedEntries orphaned user tag relations deleted", ['app' => 'DeleteOrphanedTagsJob']);
		return $deletedEntries;
	}

}
