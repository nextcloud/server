<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\UserStatus;

use DateTimeImmutable;

/**
 * Interface IUserStatus
 *
 * @since 20.0.0
 */
interface IUserStatus {
	/**
	 * @var string
	 * @since 20.0.0
	 */
	public const ONLINE = 'online';

	/**
	 * @var string
	 * @since 20.0.0
	 */
	public const AWAY = 'away';

	/**
	 * @var string
	 * @since 20.0.0
	 */
	public const DND = 'dnd';

	/**
	 * @var string
	 * @since 28.0.0
	 */
	public const BUSY = 'busy';

	/**
	 * @var string
	 * @since 20.0.0
	 */
	public const OFFLINE = 'offline';

	/**
	 * @var string
	 * @since 20.0.0
	 */
	public const INVISIBLE = 'invisible';

	/**
	 * @var string
	 * @since 25.0.0
	 */
	public const MESSAGE_CALL = 'call';

	/**
	 * @var string
	 * @since 25.0.0
	 */
	public const MESSAGE_AVAILABILITY = 'availability';

	/**
	 * @var string
	 * @since 28.0.1
	 */
	public const MESSAGE_OUT_OF_OFFICE = 'out-of-office';

	/**
	 * @var string
	 * @since 28.0.0
	 */
	public const MESSAGE_VACATION = 'vacationing';

	/**
	 * @var string
	 * @since 28.0.0
	 */
	public const MESSAGE_CALENDAR_BUSY = 'meeting';

	/**
	 * @var string
	 * @since 28.0.0
	 */
	public const MESSAGE_CALENDAR_BUSY_TENTATIVE = 'busy-tentative';

	/**
	 * Get the user this status is connected to
	 *
	 * @return string
	 * @since 20.0.0
	 */
	public function getUserId():string;

	/**
	 * Get the status
	 *
	 * It will return one of the constants defined above.
	 * It will never return invisible. In case a user marked
	 * themselves as invisible, it will return offline.
	 *
	 * @return string See IUserStatus constants
	 * @since 20.0.0
	 */
	public function getStatus():string;

	/**
	 * Get a custom message provided by the user
	 *
	 * @return string|null
	 * @since 20.0.0
	 */
	public function getMessage():?string;

	/**
	 * Get a custom icon provided by the user
	 *
	 * @return string|null
	 * @since 20.0.0
	 */
	public function getIcon():?string;

	/**
	 * Gets the time that the custom status will be cleared at
	 *
	 * @return DateTimeImmutable|null
	 * @since 20.0.0
	 */
	public function getClearAt():?DateTimeImmutable;
}
