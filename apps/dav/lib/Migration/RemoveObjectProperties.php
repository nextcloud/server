<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Migration;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RemoveObjectProperties implements IRepairStep {
	private const RESOURCE_TYPE_PROPERTY = '{DAV:}resourcetype';
	private const ME_CARD_PROPERTY = '{http://calendarserver.org/ns/}me-card';
	private const CALENDAR_TRANSP_PROPERTY = '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp';

	/**
	 * RemoveObjectProperties constructor.
	 *
	 * @param IDBConnection $connection
	 */
	public function __construct(
		private IDBConnection $connection,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Remove invalid object properties';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$query = $this->connection->getQueryBuilder();
		$updated = $query->delete('properties')
			->where($query->expr()->in('propertyname', $query->createNamedParameter([self::RESOURCE_TYPE_PROPERTY, self::ME_CARD_PROPERTY, self::CALENDAR_TRANSP_PROPERTY], IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($query->expr()->eq('propertyvalue', $query->createNamedParameter('Object'), IQueryBuilder::PARAM_STR))
			->executeStatement();

		$output->info("$updated invalid object properties removed.");
	}
}
