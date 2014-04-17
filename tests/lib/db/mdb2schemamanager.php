<?php

/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\DB;

class MDB2SchemaManager extends \PHPUnit_Framework_TestCase {

	public function tearDown() {
		\OC_DB::dropTable('table');
	}

	public function testAutoIncrement() {

		if (\OC::$server->getConfig()->getSystemValue('dbtype', 'sqlite') === 'oci') {
			$this->markTestSkipped('Adding auto increment columns in Oracle is not supported.');
		}

		$connection = \OC_DB::getConnection();
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
