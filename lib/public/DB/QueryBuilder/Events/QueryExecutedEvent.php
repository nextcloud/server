<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Andrew Summers
 *
 * @author Andrew Summers
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\DB\QueryBuilder\Events;

use OCP\EventDispatcher\Event;
use OCP\DB\QueryBuilder\IQueryBuilder;
use Doctrine\DBAL\Result;

/**
 * This event is used by apps to intercept, inspect, and potentially modify
 * the results of database queries after execution. This can be used for deep
 * integrations, restricting user access to certain data, redacting information,
 * etc.
 *
 * @see https://docs.nextcloud.com/server/latest/developer_manual/digging_deeper/projects.html
 * @since 28.0.0
 */
class QueryExecutedEvent extends Event {
	private IQueryBuilder $queryBuilder;
	private $result;

	/**
	 * @param IQueryBuilder $queryBuilder
	 * @param $result
	 * @since 28.0.0
	 */
	public function __construct(IQueryBuilder $queryBuilder, $result) {
		$this->queryBuilder = $queryBuilder;
		$this->result = $result;
	}

	/**
	 * @return IQueryBuilder
	 * @since 28.0.0
	 */
	public function getQueryBuilder(): IQueryBuilder {
		return $this->queryBuilder;
	}

	/**
	 * @param IQueryBuilder $queryBuilder
	 * @since 28.0.0
	 */
	public function setQueryBuilder(IQueryBuilder $queryBuilder) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @return Result|int|string
	 * @since 28.0.0
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * @param Result|int|string $result
	 * @since 28.0.0
	 */
	public function setResult($result) {
		$this->result = $result;
	}
}
