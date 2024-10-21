<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1032Date20230702074215 extends SimpleMigrationStep {

	public function __construct(protected IDBConnection $connection) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$propertiesTable = $schema->getTable('properties');
		$propertiesTable->addColumn('propertyvalue', Types::BLOB, [
			'notnull' => false
		]);
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
			$this->connection->executeStatement('UPDATE `*prefix*properties` SET `propertyvalue` = propertyvaluenew::bytea');
		} else {
			$query = $this->connection->getQueryBuilder();
			$query->update('properties')
				->set('propertyvalue', 'propertyvaluenew')
				->executeStatement();
		}
	}
}
