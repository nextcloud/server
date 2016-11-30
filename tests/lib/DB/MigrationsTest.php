<?php

/**
 * Copyright (c) 2016 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace Test\DB;

use OC\DB\MigrationService;

/**
 * Class MigrationsTest
 *
 * @group DB
 *
 * @package Test\DB
 */
class MigrationsTest extends \Test\TestCase {

	public function testMigrationTableCreation() {
		$m = new MigrationService();
		$appName = 'testing';
		$conf = $m->buildConfiguration($appName, \OC::$server->getDatabaseConnection());
		$this->assertTrue($conf->createMigrationTable());
		$this->assertFalse($conf->createMigrationTable());
	}

}
