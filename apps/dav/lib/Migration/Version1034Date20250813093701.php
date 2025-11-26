<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\Attributes\DataCleansing;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[DataCleansing(table: 'properties', description: 'remove commonly used custom properties set as default')]
class Version1034Date20250813093701 extends SimpleMigrationStep {
	public function __construct(
		private IDBConnection $db,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('properties')
			->where($qb->expr()->eq(
				'propertyname',
				$qb->createNamedParameter(
					'{http://owncloud.org/ns}calendar-enabled',
					IQueryBuilder::PARAM_STR,
				),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'propertyvalue',
				$qb->createNamedParameter(
					'1',
					IQueryBuilder::PARAM_STR,
				),
				IQueryBuilder::PARAM_STR,
			))
			->executeStatement();
	}
}
