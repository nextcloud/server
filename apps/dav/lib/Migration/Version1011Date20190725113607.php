<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1011Date20190725113607 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$types = ['resource', 'room'];
		foreach ($types as $type) {
			if (!$schema->hasTable($this->getMetadataTableName($type))) {
				$table = $schema->createTable($this->getMetadataTableName($type));

				$table->addColumn('id', Types::BIGINT, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]);
				$table->addColumn($type . '_id', Types::BIGINT, [
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]);
				$table->addColumn('key', Types::STRING, [
					'notnull' => true,
					'length' => 255,
				]);
				$table->addColumn('value', Types::STRING, [
					'notnull' => false,
					'length' => 4000,
				]);

				$table->setPrimaryKey(['id']);
				$table->addIndex([$type . '_id', 'key'], $this->getMetadataTableName($type) . '_idk');
			}
		}

		return $schema;
	}

	/**
	 * @param string $type
	 * @return string
	 */
	private function getMetadataTableName(string $type):string {
		return 'calendar_' . $type . 's_md';
	}
}
