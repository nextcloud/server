<?php
 /**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
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

use OCA\Encryption;
use OCA\Files_Encryption\Migration;

class Test_Migration extends \Test\TestCase {

	protected function tearDown() {
		if (OC_DB::tableExists('encryption_test')) {
			OC_DB::dropTable('encryption_test');
		}
		$this->assertTableNotExist('encryption_test');

		parent::tearDown();
	}

	protected function setUp() {
		parent::setUp();

		if (OC_DB::tableExists('encryption_test')) {
			OC_DB::dropTable('encryption_test');
		}
		$this->assertTableNotExist('encryption_test');
	}

	public function testEncryptionTableDoesNotExist() {

		$this->assertTableNotExist('encryption_test');

		$migration = new Migration('encryption_test');
		$migration->dropTableEncryption();

		$this->assertTableNotExist('encryption_test');

	}

	public function checkLastIndexId() {
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*share` ('
			.' `item_type`, `item_source`, `item_target`, `share_type`,'
			.' `share_with`, `uid_owner`, `permissions`, `stime`, `file_source`,'
			.' `file_target`, `token`, `parent`, `expiration`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
		$query->bindValue(1, 'file');
		$query->bindValue(2, 949);
		$query->bindValue(3, '/949');
		$query->bindValue(4, 0);
		$query->bindValue(5, 'migrate-test-user');
		$query->bindValue(6, 'migrate-test-owner');
		$query->bindValue(7, 23);
		$query->bindValue(8, 1402493312);
		$query->bindValue(9, 0);
		$query->bindValue(10, '/migration.txt');
		$query->bindValue(11, null);
		$query->bindValue(12, null);
		$query->bindValue(13, null);
		$this->assertEquals(1, $query->execute());

		$this->assertNotEquals('0', \OC_DB::insertid('*PREFIX*share'));

		// cleanup
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share` WHERE `file_target` = ?');
		$query->bindValue(1, '/migration.txt');
		$this->assertEquals(1, $query->execute());

	}

	public function testBrokenLastIndexId() {

		// create test table
		$this->checkLastIndexId();
		OC_DB::createDbFromStructure(__DIR__ . '/encryption_table.xml');
		$this->checkLastIndexId();
	}

	public function testDataMigration() {
		// TODO travis
		if (getenv('TRAVIS')) {
			$this->markTestSkipped('Fails on travis');
		}

		$this->assertTableNotExist('encryption_test');

		// create test table
		OC_DB::createDbFromStructure(__DIR__ . '/encryption_table.xml');
		$this->assertTableExist('encryption_test');

		OC_DB::executeAudited('INSERT INTO `*PREFIX*encryption_test` values(?, ?, ?, ?)',
		array('user1', 'server-side', 1, 1));

		// preform migration
		$migration = new Migration('encryption_test');
		$migration->dropTableEncryption();

		// assert
		$this->assertTableNotExist('encryption_test');

		$rec = \OC_Preferences::getValue('user1', 'files_encryption', 'recovery_enabled');
		$mig = \OC_Preferences::getValue('user1', 'files_encryption', 'migration_status');

		$this->assertEquals(1, $rec);
		$this->assertEquals(1, $mig);
	}

	public function testDuplicateDataMigration() {
		// TODO travis
		if (getenv('TRAVIS')) {
			$this->markTestSkipped('Fails on travis');
		}

		// create test table
		OC_DB::createDbFromStructure(__DIR__ . '/encryption_table.xml');

		// in case of duplicate entries we want to preserve 0 on migration status and 1 on recovery
		$data = array(
			array('user1', 'server-side', 1, 1),
			array('user1', 'server-side', 1, 0),
			array('user1', 'server-side', 0, 1),
			array('user1', 'server-side', 0, 0),
		);
		foreach ($data as $d) {
			OC_DB::executeAudited(
				'INSERT INTO `*PREFIX*encryption_test` values(?, ?, ?, ?)',
				$d);
		}

		// preform migration
		$migration = new Migration('encryption_test');
		$migration->dropTableEncryption();

		// assert
		$this->assertTableNotExist('encryption_test');

		$rec = \OC_Preferences::getValue('user1', 'files_encryption', 'recovery_enabled');
		$mig = \OC_Preferences::getValue('user1', 'files_encryption', 'migration_status');

		$this->assertEquals(1, $rec);
		$this->assertEquals(0, $mig);
	}

	/**
	 * @param string $table
	 */
	public function assertTableExist($table) {
		$this->assertTrue(OC_DB::tableExists($table), 'Table ' . $table . ' does not exist');
	}

	/**
	 * @param string $table
	 */
	public function assertTableNotExist($table) {
		$type=OC_Config::getValue( "dbtype", "sqlite" );
		if( $type == 'sqlite' || $type == 'sqlite3' ) {
			// sqlite removes the tables after closing the DB
			$this->assertTrue(true);
		} else {
			$this->assertFalse(OC_DB::tableExists($table), 'Table ' . $table . ' exists.');
		}
	}

}
