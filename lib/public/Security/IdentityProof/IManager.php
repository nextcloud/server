<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\IdentityProof;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IUser;

#[Consumable(since: '36.0.0')]
interface IManager {
	/**
	 * Get public and private key pair for $user
	 *
	 * @param IUser $user User to get the key for
	 * @param Algorithm $algorithm Algorithm the user key should use
	 * @since 36.0.0
	 */
	public function getUserKey(IUser $user, Algorithm $algorithm): IKey;

	/**
	 * Get public and private key pair for the server
	 *
	 * @param Algorithm $algorithm Algorithm the server key should use
	 * @since 36.0.0
	 */
	public function getServerKey(Algorithm $algorithm): IKey;

	/**
	 * Get public and private key pair for an app
	 *
	 * @param string $app App ID to get the key for
	 * @param Algorithm $algorithm Algorithm the app key should use
	 * @param string $name Optional name if the app uses multiple different keys
	 * @since 36.0.0
	 */
	public function getAppKey(string $app, Algorithm $algorithm, string $name = ''): IKey;
}
