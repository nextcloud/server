<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCP\DB\QueryBuilder;

/**
 * @since 21.0.0
 * @deprecated 21.0.0
 */
interface IResult {

	/**
	 * @return true
	 *
	 * @since 21.0.0
	 */
	public function closeCursor(): bool;

	/**
	 * @param null $fetchMode
	 *
	 * @return mixed
	 *
	 * @since 21.0.0
	 */
	public function fetch($fetchMode = null);

	/**
	 * @param null $fetchMode
	 *
	 * @return mixed[]
	 *
	 * @since 21.0.0
	 */
	public function fetchAll($fetchMode = null): array;

	/**
	 * @return mixed
	 *
	 * @since 21.0.0
	 * @deprecated 21.0.0 use \OCP\DB\QueryBuilder\IResult::fetchOne
	 */
	public function fetchColumn();

	/**
	 * @param int $columnIndex
	 *
	 * @return false|mixed|void
	 *
	 * @since 21.0.0
	 */
	public function fetchOne($columnIndex = 0);

	/**
	 * @param mixed[]|null $params
	 *
	 * @todo must not return dbal type
	 *
	 * @return bool|void
	 *
	 * @since 21.0.0
	 * @deprecated 21.0.0
	 */
	public function execute($params = null): \Doctrine\DBAL\Driver\Result;

	/**
	 * @return int
	 *
	 * @since 21.0.0
	 */
	public function rowCount(): int;
}
