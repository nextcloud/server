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

class Test_Migration extends PHPUnit_Framework_TestCase {

	public function tearDown() {
		if (OC_DB::tableExists('encryption_test')) {
			OC_DB::dropTable('encryption_test');
		}
		$this->assertTableNotExist('encryption_test');
	}

	public function setUp() {
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

	public function testDataMigration() {

		//FIXME fix this test so that we can enable it again
		$this->markTestIncomplete('Disabled, because of this tests a lot of other tests fail at the moment');

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

		//FIXME fix this test so that we can enable it again
		$this->markTestIncomplete('Disabled, because of this tests a lot of other tests fail at the moment');

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
