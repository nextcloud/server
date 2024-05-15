<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
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
 */


namespace OCP\TaskProcessing;

/**
 * This is the interface that is implemented by apps that
 * implement a task processing provider
 * @since 30.0.0
 */
interface IProvider {
	/**
	 * The unique id of this provider
	 * @since 30.0.0
	 */
	public function getId(): string;

	/**
	 * The localized name of this provider
	 * @since 30.0.0
	 */
	public function getName(): string;

	/**
	 * Returns the task type id of the task type, that this
	 * provider handles
	 *
	 * @since 30.0.0
	 * @return string
	 */
	public function getTaskTypeId(): string;

	/**
	 * @return int The expected average runtime of a task in seconds
	 * @since 30.0.0
	 */
	public function getExpectedRuntime(): int;

	/**
	 * Returns the shape of optional input parameters
	 *
	 * @since 30.0.0
	 * @psalm-return ShapeDescriptor[]
	 */
	public function getOptionalInputShape(): array;

	/**
	 * Returns the shape of optional output parameters
	 *
	 * @since 30.0.0
	 * @psalm-return ShapeDescriptor[]
	 */
	public function getOptionalOutputShape(): array;
}
