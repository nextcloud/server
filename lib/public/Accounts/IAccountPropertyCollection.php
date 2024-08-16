<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
