<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation;

/**
 * Interface for resolving federated cloud ids
 *
 * @since 12.0.0
 */
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
}
