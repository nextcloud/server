<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Avatar;

use OCP\Files\SimpleFS\InMemoryFile;
use OCP\Files\SimpleFS\ISimpleFile;
use Psr\Log\LoggerInterface;

/**
 * This class represents a guest user's avatar.
 */
class GuestAvatar extends Avatar {
	/**
	 * GuestAvatar constructor.
	 *
	 * @param string $userDisplayName The guest user display name
	 */
	public function __construct(
		private string $userDisplayName,
		LoggerInterface $logger,
	) {
		parent::__construct($logger);
	}

	/**
	 * Tests if the user has an avatar.
	 */
	public function exists(): bool {
		// Guests always have an avatar.
		return true;
	}

	/**
	 * Setting avatars isn't implemented for guests.
	 *
	 * @param \OCP\IImage|resource|string $data
	 */
	public function set($data): void {
		// unimplemented for guest user avatars
	}

	/**
	 * Removing avatars isn't implemented for guests.
	 */
	public function remove(bool $silent = false): void {
		// unimplemented for guest user avatars
	}

	/**
	 * Generates an avatar for the guest.
	 */
	public function getFile(int $size, bool $darkTheme = false): ISimpleFile {
		$avatar = $this->generateAvatar($this->userDisplayName, $size, $darkTheme);
		return new InMemoryFile('avatar.png', $avatar);
	}

	/**
	 * Updates the display name if changed.
	 *
	 * @param string $feature The changed feature
	 * @param mixed $oldValue The previous value
	 * @param mixed $newValue The new value
	 */
	public function userChanged(string $feature, $oldValue, $newValue): void {
		if ($feature === 'displayName') {
			$this->userDisplayName = $newValue;
		}
	}

	/**
	 * Guests don't have custom avatars.
	 */
	public function isCustomAvatar(): bool {
		return false;
	}
}
