<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Interface for resolving federated cloud ids
 *
 * @since 12.0.0
 */
#[Consumable(since: '12.0.0')]
interface ICloudIdManager {
	/**
	 * @param string $cloudId
	 * @return ICloudId
	 * @throws \InvalidArgumentException
	 *
	 * @since 12.0.0
	 */
	public function resolveCloudId(string $cloudId): ICloudId;

	/**
	 * Get the cloud id for a remote user
	 *
	 * @param string $user
	 * @param string|null $remote (optional since 23.0.0 for local users)
	 * @return ICloudId
	 *
	 * @since 12.0.0
	 */
	public function getCloudId(string $user, ?string $remote): ICloudId;

	/**
	 * Check if the input is a correctly formatted cloud id
	 *
	 * @param string $cloudId
	 * @return bool
	 *
	 * @since 12.0.0
	 */
	public function isValidCloudId(string $cloudId): bool;

	/**
	 * remove scheme/protocol from an url
	 *
	 * @param string $url
	 * @param bool $httpsOnly
	 *
	 * @return string
	 * @since 28.0.0
	 * @since 30.0.0 - Optional parameter $httpsOnly was added
	 */
	public function removeProtocolFromUrl(string $url, bool $httpsOnly = false): string;

	/**
	 * @param string $id The remote cloud id
	 * @param string $user The user id on the remote server
	 * @param string $remote The base address of the remote server
	 * @param ?string $displayName The displayname of the remote user
	 *
	 * @since 32.0.0
	 */
	public function createCloudId(string $id, string $user, string $remote, ?string $displayName = null): ICloudId;

	/**
	 * @param $resolver The cloud id resolver to register
	 *
	 * @since 32.0.0
	 */
	public function registerCloudIdResolver(ICloudIdResolver $resolver): void;

	/**
	 * @param $resolver The cloud id resolver to unregister
	 *
	 * @since 32.0.0
	 */
	public function unregisterCloudIdResolver(ICloudIdResolver $resolver): void;
}
