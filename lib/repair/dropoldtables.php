<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;


use OC\DB\Connection;
use OC\Hooks\BasicEmitter;
use OC\RepairStep;

class DropOldTables extends BasicEmitter implements RepairStep {

	/** @var Connection */
	protected $connection;

	/**
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 */
	public function getName() {
		return 'Drop old database tables';
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @throws \Exception in case of failure
	 */
	public function run() {
		foreach ($this->oldDatabaseTables() as $tableName) {
			if ($this->connection->tableExists($tableName)){
				$this->emit('\OC\Repair', 'info', [
					sprintf('Table %s has been deleted', $tableName)
				]);
				$this->connection->dropTable($tableName);
			}
		}
	}

	/**
	 * Returns a list of outdated tables which are not used anymore
	 * @return array
	 */
	protected function oldDatabaseTables() {
		return [
			'calendar_calendars',
			'calendar_objects',
			'calendar_share_calendar',
			'calendar_share_event',
			'foldersize',
			'fscache',
			'locks',
			'log',
			'media_albums',
			'media_artists',
			'media_sessions',
			'media_songs',
			'media_users',
			'permissions',
			'pictures_images_cache',
			'queuedtasks',
			'sharing',
		];
	}
}
