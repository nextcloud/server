<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Michael Weimann <mail@michael-weimann.eu>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Vincent Petry <vincent@nextcloud.com>
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

use OC\NotSquareException;
use OC\User\User;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IImage;
use Psr\Log\LoggerInterface;

/**
 * This class represents a registered user's placeholder avatar.
 *
 * It generates an image based on the user's initials and caches it on storage
 * for faster retrieval, unlike the GuestAvatar.
 */
class PlaceholderAvatar extends Avatar {
	public function __construct(
		private ISimpleFolder $folder,
		private User $user,
		LoggerInterface $logger,
	) {
		parent::__construct($logger);
	}

	/**
	 * Check if an avatar exists for the user
	 */
	public function exists(): bool {
		return true;
	}

	/**
	 * Sets the users avatar.
	 *
	 * @param IImage|resource|string $data An image object, imagedata or path to set a new avatar
	 * @throws \Exception if the provided file is not a jpg or png image
	 * @throws \Exception if the provided image is not valid
	 * @throws NotSquareException if the image is not square
	 */
	public function set($data): void {
		// unimplemented for placeholder avatars
	}

	/**
	 * Removes the users avatar.
	 */
	public function remove(bool $silent = false): void {
		$avatars = $this->folder->getDirectoryListing();

		foreach ($avatars as $avatar) {
			$avatar->delete();
		}
	}

	/**
	 * Returns the avatar for an user.
	 *
	 * If there is no avatar file yet, one is generated.
	 *
	 * @throws NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function getFile(int $size, bool $darkTheme = false): ISimpleFile {
		$ext = 'png';

		if ($size === -1) {
			$path = 'avatar-placeholder' . ($darkTheme ? '-dark' : '') . '.' . $ext;
		} else {
			$path = 'avatar-placeholder' . ($darkTheme ? '-dark' : '') . '.' . $size . '.' . $ext;
		}

		try {
			$file = $this->folder->getFile($path);
		} catch (NotFoundException $e) {
			if ($size <= 0) {
				throw new NotFoundException;
			}

			if (!$data = $this->generateAvatarFromSvg($size, $darkTheme)) {
				$data = $this->generateAvatar($this->getDisplayName(), $size, $darkTheme);
			}

			try {
				$file = $this->folder->newFile($path);
				$file->putContent($data);
			} catch (NotPermittedException $e) {
				$this->logger->error('Failed to save avatar placeholder for ' . $this->user->getUID());
				throw new NotFoundException();
			}
		}

		return $file;
	}

	/**
	 * Returns the user display name.
	 */
	public function getDisplayName(): string {
		return $this->user->getDisplayName();
	}

	/**
	 * Handles user changes.
	 *
	 * @param string $feature The changed feature
	 * @param mixed $oldValue The previous value
	 * @param mixed $newValue The new value
	 * @throws NotPermittedException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function userChanged(string $feature, $oldValue, $newValue): void {
		$this->remove();
	}

	/**
	 * Check if the avatar of a user is a custom uploaded one
	 */
	public function isCustomAvatar(): bool {
		return false;
	}
}
