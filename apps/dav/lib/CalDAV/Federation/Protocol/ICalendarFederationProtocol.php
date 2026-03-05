<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation\Protocol;

interface ICalendarFederationProtocol {
	public const PROP_VERSION = 'version';

	/**
	 * Get the version of this protocol implementation.
	 */
	public function getVersion(): string;

	/**
	 * Convert the protocol to an associative array to be sent to a remote instance.
	 * The resulting array still needs to be merged with the base protocol from the share!
	 */
	public function toProtocol(): array;
}
