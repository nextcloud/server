<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Accounts;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Interface IAccountPropertyCollection
 *
 * @package OCP\Accounts
 *
 * @since 22.0.0
 */
interface IAccountPropertyCollection extends JsonSerializable {
	/**
	 * returns the collection name
	 *
	 * @since 22.0.0
	 */
	public function getName(): string;

	/**
	 * set properties of this collection
	 *
	 * @param IAccountProperty[] $properties
	 * @throws InvalidArgumentException
	 * @since 22.0.0
	 */
	public function setProperties(array $properties): IAccountPropertyCollection;

	/**
	 * @return IAccountProperty[]
	 * @since 22.0.0
	 */
	public function getProperties(): array;

	/**
	 * adds a property to this collection
	 *
	 * @throws InvalidArgumentException
	 * @since 22.0.0
	 */
	public function addProperty(IAccountProperty $property): IAccountPropertyCollection;

	/**
	 * adds a property to this collection with only specifying the value
	 *
	 * @throws InvalidArgumentException
	 * @since 22.0.0
	 */
	public function addPropertyWithDefaults(string $value): IAccountPropertyCollection;

	/**
	 * removes a property of this collection
	 *
	 * @since 22.0.0
	 */
	public function removeProperty(IAccountProperty $property): IAccountPropertyCollection;

	/**
	 * removes a property identified by its value
	 *
	 * @since 22.0.0
	 */
	public function removePropertyByValue(string $value): IAccountPropertyCollection;

	/**
	 * retrieves a property identified by its value. null, if none was found.
	 *
	 * Returns only the first property if there are more with the same value.
	 *
	 * @since 23.0.0
	 */
	public function getPropertyByValue(string $value): ?IAccountProperty;
}
