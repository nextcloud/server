<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Migration;

use OC\Migration\MetadataManager;
use OCP\App\IAppManager;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\DropTable;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Server;

/**
 * Class MetadataManagerTest
 *
 * @package Test\DB
 */
class MetadataManagerTest extends \Test\TestCase {
	private IAppManager $appManager;

	protected function setUp(): void {
		parent::setUp();
		$this->appManager = Server::get(IAppManager::class);
	}

	public function testExtractMigrationAttributes(): void {
		$metadataManager = Server::get(MetadataManager::class);
		$this->appManager->loadApp('testing');

		$this->assertEquals(
			self::getMigrationMetadata(),
			json_decode(json_encode($metadataManager->extractMigrationAttributes('testing')), true),
		);

		$this->appManager->disableApp('testing');
	}

	public function testDeserializeMigrationMetadata(): void {
		$metadataManager = Server::get(MetadataManager::class);
		$this->assertEquals(
			[
				'core' => [],
				'apps' => [
					'testing' => [
						'30000Date20240102030405' => [
							new DropTable('old_table'),
							new CreateTable('new_table',
								description: 'Table is used to store things, but also to get more things',
								notes:       ['this is a notice', 'and another one, if really needed']
							),
							new AddColumn('my_table'),
							new AddColumn('my_table', 'another_field'),
							new AddColumn('other_table', 'last_one', ColumnType::DATE),
							new AddIndex('my_table'),
							new AddIndex('my_table', IndexType::PRIMARY),
							new DropColumn('other_table'),
							new DropColumn('other_table', 'old_column',
								description: 'field is not used anymore and replaced by \'last_one\''
							),
							new DropIndex('other_table'),
							new ModifyColumn('other_table'),
							new ModifyColumn('other_table', 'this_field'),
							new ModifyColumn('other_table', 'this_field', ColumnType::BIGINT)
						]
					]
				]
			],
			$metadataManager->getMigrationsAttributesFromReleaseMetadata(
				[
					'core' => [],
					'apps' => ['testing' => self::getMigrationMetadata()]
				]
			)
		);
	}

	private static function getMigrationMetadata(): array {
		return [
			'30000Date20240102030405' => [
				[
					'class' => 'OCP\\Migration\\Attributes\\DropTable',
					'table' => 'old_table',
					'description' => '',
					'notes' => [],
					'columns' => []
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\CreateTable',
					'table' => 'new_table',
					'description' => 'Table is used to store things, but also to get more things',
					'notes'
						=> [
							'this is a notice',
							'and another one, if really needed'
						],
					'columns' => []
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddColumn',
					'table' => 'my_table',
					'description' => '',
					'notes' => [],
					'name' => '',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddColumn',
					'table' => 'my_table',
					'description' => '',
					'notes' => [],
					'name' => 'another_field',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => 'last_one',
					'type' => 'date'
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddIndex',
					'table' => 'my_table',
					'description' => '',
					'notes' => [],
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\AddIndex',
					'table' => 'my_table',
					'description' => '',
					'notes' => [],
					'type' => 'primary'
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\DropColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => '',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\DropColumn',
					'table' => 'other_table',
					'description' => 'field is not used anymore and replaced by \'last_one\'',
					'notes' => [],
					'name' => 'old_column',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\DropIndex',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\ModifyColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => '',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\ModifyColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => 'this_field',
					'type' => ''
				],
				[
					'class' => 'OCP\\Migration\\Attributes\\ModifyColumn',
					'table' => 'other_table',
					'description' => '',
					'notes' => [],
					'name' => 'this_field',
					'type' => 'bigint'
				],
			]
		];
	}
}
