<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Support\Subscription;

/**
 * @since 17.0.0
 */
interface ISubscription {
	/**
	 * Indicates if a valid subscription is available
	 *
	 * @since 17.0.0
	 */
	public function hasValidSubscription(): bool;

	/**
	 * Indicates if the subscription has extended support
	 *
	 * @since 17.0.0
	 */
	public function hasExtendedSupport(): bool;

	/**
	 * Indicates if a hard user limit is reached and no new users should be created
	 *
	 * @since 21.0.0
	 */
	public function isHardUserLimitReached(): bool;
}
