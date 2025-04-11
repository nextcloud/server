<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\EventDispatcher;

use OCP\Files\FileInfo;
use OCP\IUser;

/**
 * Contains helper static methods to serialize OCP classes into arrays to pass json_encode.
 * Useful for events implementing \JsonSerializable.
 *
 * @since 30.0.0
 */

final class JsonSerializer {
	/**
	 * @since 30.0.0
	 */
	public static function serializeFileInfo(FileInfo $node): array {
		return [
			'id' => $node->getId(),
			'path' => $node->getPath(),
		];
	}

	/**
	 * @since 30.0.0
	 */
	public static function serializeUser(IUser $user): array {
		return [
			'uid' => $user->getUID(),
			'displayName' => $user->getDisplayName(),
		];
	}
}
