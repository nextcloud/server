<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Support\Subscription;

use OCP\HintException;

/**
 * @since 26.0.0
 */
interface IAssertion {
	/**
	 * This method throws a localized exception when user limits are exceeded,
	 * if applicable. Notifications are also created in that case. It is a
	 * shorthand for a check against IRegistry::delegateIsHardUserLimitReached().
	 *
	 * @throws HintException
	 * @since 26.0.0
	 */
	public function createUserIsLegit(): void;
}
