<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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


use OCA\Files_Sharing\Tests\TestCase;
use OCA\Files_Sharing\Migration;

class MigrationTest extends TestCase {

	/**
	 * @var \OCP\IDBConnection
	 */
	private $connection;

	function __construct() {
		parent::__construct();

		$this->connection = \OC::$server->getDatabaseConnection();
	}

	function testAddAccept() {

		$query = $this->connection->prepare('
			INSERT INTO `*PREFIX*share_external`
			(`remote`, `share_token`, `password`, `name`, `owner`, `user`, `mountpoint`, `mountpoint_hash`, `remote_id`, `accepted`)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		');

		for ($i = 0; $i < 10; $i++) {
			$query->execute(array('remote', 'token', 'password', 'name', 'owner', 'user', 'mount point', $i, $i, 0));
		}

		$query = $this->connection->prepare('SELECT `id` FROM `*PREFIX*share_external`');
		$query->execute();
		$dummyEntries = $query->fetchAll();

		$this->assertSame(10, count($dummyEntries));

		$m = new Migration();
		$m->addAcceptRow();

		// verify result
		$query = $this->connection->prepare('SELECT `accepted` FROM `*PREFIX*share_external`');
		$query->execute();
		$results = $query->fetchAll();
		$this->assertSame(10, count($results));

		foreach ($results as $r) {
			$this->assertSame(1, (int) $r['accepted']);
		}

		// cleanup
		$cleanup = $this->connection->prepare('DELETE FROM `*PREFIX*share_external`');
		$cleanup->execute();
	}

}
