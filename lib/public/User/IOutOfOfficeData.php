<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User;

use JsonSerializable;
use OCP\IUser;

/**
 * DTO to hold out-of-office information of a user
 *
 * @psalm-type OutOfOfficeData = array{
 *      id: string,
 *      userId: string,
 *      startDate: int,
 *      endDate: int,
 *      shortMessage: string,
 *      message: string,
 * 		replacementUserId: ?string,
 * 		replacementUserDisplayName: ?string
 * }
 *
 * @since 28.0.0
 */
interface IOutOfOfficeData extends JsonSerializable {
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

	/**
	 * Get the replacement user id for auto responders and similar
	 *
	 * @since 30.0.0
	 */
	public function getReplacementUserId(): ?string;

	/**
	 * Get the replacement user displayName for auto responders and similar
	 *
	 * @since 30.0.0
	 */
	public function getReplacementUserDisplayName(): ?string;

	/**
	 * @return OutOfOfficeData
	 *
	 * @since 28.0.0
	 */
	public function jsonSerialize(): array;
}
