<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\IUser;

/**
 * Nextcloud 2FA provider registry for stateful 2FA providers
 *
 * This service keeps track of which providers are currently active for a specific
 * user. Stateful 2FA providers (IStatefulProvider) must use this service to save
 * their enabled/disabled state.
 *
 * @since 14.0.0
 */
interface IRegistry {
	/**
	 * @since 15.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_PROVIDER_ENABLED = self::class . '::enable';

	/**
	 * @since 15.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_PROVIDER_DISABLED = self::class . '::disable';

	/**
	 * Get a key-value map of providers and their enabled/disabled state for
	 * the given user.
	 *
	 * @since 14.0.0
	 * @return array<string, bool> where the array key is the provider ID (string) and the
	 *                             value is the enabled state (bool)
	 */
	public function getProviderStates(IUser $user): array;

	/**
	 * Enable the given 2FA provider for the given user
	 *
	 * @since 14.0.0
	 */
	public function enableProviderFor(IProvider $provider, IUser $user);

	/**
	 * Disable the given 2FA provider for the given user
	 *
	 * @since 14.0.0
	 */
	public function disableProviderFor(IProvider $provider, IUser $user);

	/**
	 * Cleans up all entries of the provider with the given id. This is only
	 * necessary in edge-cases where an admin disabled and/or uninstalled a
	 * provider app. Invoking this method will make sure outdated provider
	 * associations are removed so that users can log in.
	 *
	 * @since 15.0.0
	 *
	 * @param string $providerId
	 *
	 * @return void
	 */
	public function cleanUp(string $providerId);
}
