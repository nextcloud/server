<?php

/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\DB;

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;

class MDB2SchemaManager extends \Test\TestCase {

	protected function tearDown() {
		// do not drop the table for Oracle as it will create a bogus transaction
		// that will break the following test suites requiring transactions
		if (\OC::$server->getConfig()->getSystemValue('dbtype', 'sqlite') === 'oci') {
			return;
		}
		\OC_DB::dropTable('table');

		parent::tearDown();
	}

	public function testAutoIncrement() {

		$connection = \OC_DB::getConnection();
		if ($connection->getDatabasePlatform() instanceof OraclePlatform) {
			$this->markTestSkipped('Adding auto increment columns in Oracle is not supported.');
		}
		if ($connection->getDatabasePlatform() instanceof SQLServerPlatform) {
			$this->markTestSkipped('DB migration tests are not supported on MSSQL');
		}

		$manager = new \OC\DB\MDB2SchemaManager($connection);

		$manager->createDbFromStructure(__DIR__ . '/ts-autoincrement-before.xml');
		$connection->executeUpdate('insert into `*PREFIX*table` values (?)', array('abc'));
		$connection->executeUpdate('insert into `*PREFIX*table` values (?)', array('abc'));
		$connection->executeUpdate('insert into `*PREFIX*table` values (?)', array('123'));
		$connection->executeUpdate('insert into `*PREFIX*table` values (?)', array('123'));
		$manager->updateDbFromStructure(__DIR__ . '/ts-autoincrement-after.xml');

		$this->assertTrue(true);
	}

}
