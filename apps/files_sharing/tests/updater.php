<?php
/**
 * ownCloud
 *
 * @author Morris Jobke
 * @copyright 2014 Morris Jobke <morris.jobke@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Class Test_Files_Sharing_Updater
 */
class Test_Files_Sharing_Updater extends \PHPUnit_Framework_TestCase {

	function setUp() {
		// some previous tests didn't clean up and therefore this has to be done here
		// FIXME: DIRTY HACK - TODO: find tests, that don't clean up and fix it there
		$this->tearDown();

		$addShares = \OC_DB::prepare('INSERT INTO `*PREFIX*share` (file_source, id, item_type, uid_owner) VALUES (?, ?, \'file\', 1)');
		$shares = array(1, 2, 3);
		foreach($shares as $share) {
			// the number is used as item_source and id
			$addShares->execute(array($share, $share));
		}
		// add items except one - because this is the test case for the broken share table
		$addItems = \OC_DB::prepare('INSERT INTO `*PREFIX*filecache` (fileid, storage, path_hash, ' .
			'parent, mimetype, mimepart, size, mtime, storage_mtime) ' .
			'VALUES (?, 1, ?, 1, 1, 1, 1, 1, 1)');
		$items = array(1, 3);
		foreach($items as $item) {
			// the number is used as file_id and path_hash
			$addItems->execute(array($item, $item));
		}
	}

	function tearDown() {
		$removeShares = \OC_DB::prepare('DELETE FROM `*PREFIX*share`');
		$removeShares->execute();
		$removeItems = \OC_DB::prepare('DELETE FROM `*PREFIX*filecache`');
		$removeItems->execute();
	}

	/**
	 * @medium
	 */
	function testRemoveBrokenShares() {
		// check if there are just 3 shares (see setUp - precondition: empty table)
		$countShares = \OC_DB::prepare('SELECT COUNT(id) FROM `*PREFIX*share`');
		$result = $countShares->execute()->fetchOne();
		$this->assertEquals(3, $result);

		// check if there are just 2 items (see setUp - precondition: empty table)
		$countItems = \OC_DB::prepare('SELECT COUNT(fileid) FROM `*PREFIX*filecache`');
		$result = $countItems->execute()->fetchOne();
		$this->assertEquals(2, $result);

		// execute actual code which should be tested
		\OC\Files\Cache\Shared_Updater::fixBrokenSharesOnAppUpdate();

		// check if there are just 2 shares (one gets killed by the code as there is no filecache entry for this)
		$countShares = \OC_DB::prepare('SELECT COUNT(id) FROM `*PREFIX*share`');
		$result = $countShares->execute()->fetchOne();
		$this->assertEquals(2, $result);

		// check if the share of file '2' is removed as there is no entry for this in filecache table
		$countShares = \OC_DB::prepare('SELECT COUNT(id) FROM `*PREFIX*share` WHERE file_source = 2');
		$result = $countShares->execute()->fetchOne();
		$this->assertEquals(0, $result);

		// check if there are just 2 items
		$countItems = \OC_DB::prepare('SELECT COUNT(fileid) FROM `*PREFIX*filecache`');
		$result = $countItems->execute()->fetchOne();
		$this->assertEquals(2, $result);
	}
}
