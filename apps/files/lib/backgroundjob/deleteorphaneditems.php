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
class DeleteOrphanedItems extends TimedJob {

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
		$this->cleanComments();
		$this->cleanCommentMarkers();
	}

	/**
	 * Deleting orphaned system tag mappings
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanUp($table, $idCol, $typeCol) {
		$subQuery = $this->connection->getQueryBuilder();
		$subQuery->select($subQuery->expr()->literal('1'))
			->from('filecache', 'f')
			->where($subQuery->expr()->eq($idCol, 'f.fileid'));

		$query = $this->connection->getQueryBuilder();
		$deletedEntries = $query->delete($table)
			->where($query->expr()->eq($typeCol, $query->expr()->literal('files')))
			->andWhere($query->expr()->isNull($query->createFunction('(' . $subQuery->getSql() . ')')))
			->execute();

		return $deletedEntries;
	}

	/**
	 * Deleting orphaned system tag mappings
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanSystemTags() {
		$deletedEntries = $this->cleanUp('systemtag_object_mapping', 'objectid', 'objecttype');
		$this->logger->debug("$deletedEntries orphaned system tag relations deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned user tag mappings
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanUserTags() {
		$deletedEntries = $this->cleanUp('vcategory_to_object', 'objid', 'type');
		$this->logger->debug("$deletedEntries orphaned user tag relations deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned comments
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanComments() {
		$deletedEntries = $this->cleanUp('comments', 'object_id', 'object_type');
		$this->logger->debug("$deletedEntries orphaned comments deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned comment read markers
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanCommentMarkers() {
		$deletedEntries = $this->cleanUp('comments_read_markers', 'object_id', 'object_type');
		$this->logger->debug("$deletedEntries orphaned comment read marks deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

}
