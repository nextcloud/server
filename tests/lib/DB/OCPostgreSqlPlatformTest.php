<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\DB;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * Class OCPostgreSqlPlatformTest
 *
 * custom OCPostgreSqlPlatform behavior has been upstreamed, test is left to
 * ensure behavior stays correct.
 *
 * @group DB
 *
 * @package Test\DB
 */
class OCPostgreSqlPlatformTest extends \Test\TestCase {
	public function testAlterBigint(): void {
		$platform = new PostgreSQLPlatform();
		$sourceSchema = new Schema();
		$targetSchema = new Schema();

		$this->createTableAndColumn($sourceSchema, Types::INTEGER);
		$this->createTableAndColumn($targetSchema, Types::BIGINT);

		$comparator = new Comparator();
		$diff = $comparator->compare($sourceSchema, $targetSchema);
		$sqlStatements = $diff->toSql($platform);
		$this->assertContains(
			'ALTER TABLE poor_yorick ALTER id TYPE BIGINT',
			$sqlStatements,
			true
		);

		$this->assertNotContains(
			'ALTER TABLE poor_yorick ALTER id DROP DEFAULT',
			$sqlStatements,
			true
		);
	}

	protected function createTableAndColumn($schema, $type) {
		$table = $schema->createTable('poor_yorick');
		$table->addColumn('id', $type, [
			'autoincrement' => true,
			'unsigned' => true,
			'notnull' => true,
			'length' => 11,
		]);
	}
}
