<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Remote;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Provides some basic info about a remote Nextcloud instance
 *
 * @since 13.0.0
 * @deprecated 23.0.0
 */
#[Consumable(since: '13.0.0')]
interface IInstance {
	/**
	 * @return string The url of the remote server without protocol
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getUrl(): string;

	/**
	 * @return string The of the remote server with protocol
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getFullUrl(): string;

	/**
	 * @return string The full version string in '13.1.2.3' format
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getVersion(): string;

	/**
	 * @return 'http'|'https'
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getProtocol(): string;

	/**
	 * Check that the remote server is installed and not in maintenance mode
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 *
	 * @return bool
	 */
	public function isActive(): bool;
}
