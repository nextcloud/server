<?php
/**
 * @copyright Copyright (c) 2021, Thomas Citharel <nextcloud@tcit.fr>.
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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

	/** @var IDBConnection */
	private $connection;

	/**
	 * RemoveObjectProperties constructor.
	 *
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
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
