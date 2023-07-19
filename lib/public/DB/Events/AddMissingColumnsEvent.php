<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\DB\Events;

/**
 * Event to allow apps to register information about missing database columns
 *
 * This event will be dispatched for checking on the admin settings and when running
 * occ db:add-missing-columns which will then create those columns
 *
 * @since 28.0.0
 */
class AddMissingColumnsEvent extends \OCP\EventDispatcher\Event {
	/** @var array<array-key, array{tableName: string, columnName: string, typeName: string, options: array{}}> */
	private array $missingColumns = [];

	/**
	 * @param mixed[] $options
	 * @since 28.0.0
	 */
	public function addMissingColumn(string $tableName, string $columnName, string $typeName, array $options): void {
		$this->missingColumns[] = [
			'tableName' => $tableName,
			'columnName' => $columnName,
			'typeName' => $typeName,
			'options' => $options,
		];
	}

	/**
	 * @since 28.0.0
	 * @return array<array-key, array{tableName: string, columnName: string, typeName: string, options: array{}}>
	 */
	public function getMissingColumns(): array {
		return $this->missingColumns;
	}
}
