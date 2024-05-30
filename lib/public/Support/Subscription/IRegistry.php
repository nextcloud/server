<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Support\Subscription;

use OCP\Notification\IManager;
use OCP\Support\Subscription\Exception\AlreadyRegisteredException;

/**
 * @since 17.0.0
 */
interface IRegistry {
	/**
	 * Register a subscription instance. In case it is called multiple times an
	 * exception is thrown
	 *
	 * @param ISubscription $subscription
	 * @throws AlreadyRegisteredException
	 *
	 * @since 17.0.0
	 * @deprecated 20.0.0 use registerService
	 */
	public function register(ISubscription $subscription): void;

	/**
	 * Register a subscription handler. The service has to implement the ISubscription interface.
	 * In case this is called multiple times an exception is thrown.
	 *
	 * @param string $subscriptionService
	 * @throws AlreadyRegisteredException
	 *
	 * @since 20.0.0
	 */
	public function registerService(string $subscriptionService): void;

	/**
	 * Fetches the list of app IDs that are supported by the subscription
	 *
	 * @since 17.0.0
	 */
	public function delegateGetSupportedApps(): array;

	/**
	 * Indicates if a valid subscription is available
	 *
	 * @since 17.0.0
	 */
	public function delegateHasValidSubscription(): bool;

	/**
	 * Indicates if the subscription has extended support
	 *
	 * @since 17.0.0
	 */
	public function delegateHasExtendedSupport(): bool;

	/**
	 * Indicates if a hard user limit is reached and no new users should be created
	 *
	 * @param IManager|null $notificationManager
	 * @since 21.0.0
	 */
	public function delegateIsHardUserLimitReached(?IManager $notificationManager = null): bool;
}
