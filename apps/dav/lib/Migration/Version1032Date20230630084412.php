<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Doctrine\DBAL\Types\Type;

/**
 * Cleaning invalid serialized propertyvalues and converting the column type to blob
 */
class Version1032Date20230630084412 extends SimpleMigrationStep {

	public function __construct(protected IDBConnection $connection, protected IConfig $config) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		/**
		 * Cleaning the invalid serialized propertyvalues because of NULL values in a text field
		 */
		$query = $this->connection->getQueryBuilder();
		$query->delete('properties')
			->where($query->expr()->eq('valuetype', $query->createNamedParameter(3)))
			->executeStatement();
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
		if ($propertiesTable->hasColumn('propertyvaluenew')) {
			return null;
		}
		$propertiesTable->addColumn('propertyvaluenew', Types::TEXT, [
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
		$query = $this->connection->getQueryBuilder();
		$query->update('properties')
			->set('propertyvaluenew', 'propertyvalue')
			->executeStatement();
	}
}
