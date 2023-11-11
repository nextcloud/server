<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCP\User;

use OCP\IUser;

/**
 * DTO to hold out-of-office information of a user
 *
 * @since 28.0.0
 */
interface IOutOfOfficeData {
	/**
	 * Get the unique token assigned to the current out-of-office event
	 *
	 * @since 28.0.0
	 */
	public function getId(): string;

	/**
	 * @since 28.0.0
	 */
	public function getUser(): IUser;

	/**
	 * Get the accurate out-of-office start date
	 *
	 * This event is not guaranteed to be emitted exactly at start date
	 *
	 * @since 28.0.0
	 */
	public function getStartDate(): int;

	/**
	 * Get the (preliminary) out-of-office end date
	 *
	 * @since 28.0.0
	 */
	public function getEndDate(): int;

	/**
	 * Get the short summary text displayed in the user status and similar
	 *
	 * @since 28.0.0
	 */
	public function getShortMessage(): string;

	/**
	 * Get the long out-of-office message for auto responders and similar
	 *
	 * @since 28.0.0
	 */
	public function getMessage(): string;
}
