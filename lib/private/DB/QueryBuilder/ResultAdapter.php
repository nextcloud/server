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

namespace OC\DB\QueryBuilder;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use OCP\DB\QueryBuilder\IResult;
use PDO;

/**
 * Adapts DBAL 2.6 API for DBAL 3.x for backwards compatibility of a leaked type
 *
 * @deprecated
 */
class ResultAdapter implements IResult {

	/** @var Result */
	private $inner;

	public function __construct(Result $inner) {
		$this->inner = $inner;
	}

	public function closeCursor(): bool {
		$this->inner->free();

		return true;
	}

	/**
	 * @todo $cursorOrientation and $cursorOffset not used?!
	 */
	public function fetch($fetchMode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0) {
		return $this->inner->fetch($fetchMode);
	}

	/**
	 * @todo $cursorOrientation and $cursorOffset not used?!
	 */
	public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null): array {
		return $this->inner->fetchAll($fetchMode);
	}

	public function fetchColumn($columnIndex = 0) {
		return $this->inner->fetchOne();
	}

	public function execute($params = null): \Doctrine\DBAL\Driver\Result {
		return $this->inner->execute($params);
	}

	public function rowCount(): int {
		return $this->inner->rowCount();
	}
}
