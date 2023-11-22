<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Michael Weimann <mail@michael-weimann.eu>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Michael Weimann <mail@michael-weimann.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Avatar;

use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\InMemoryFile;
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
	 * Returns the guest user display name.
	 */
	public function getDisplayName(): string {
		return $this->userDisplayName;
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
