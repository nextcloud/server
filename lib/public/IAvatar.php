<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP;

use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * This class provides avatar functionality
 * @since 6.0.0
 */
interface IAvatar {
	/**
	 * Get the users avatar
	 *
	 * @param int $size size in px of the avatar, avatars are square, defaults to 64, -1 can be used to not scale the image
	 * @param bool $darkTheme Should the generated avatar be dark themed
	 * @return false|\OCP\IImage containing the avatar or false if there's no image
	 * @since 6.0.0 - size of -1 was added in 9.0.0
	 */
	public function get(int $size = 64, bool $darkTheme = false);

	/**
	 * Check if an avatar exists for the user
	 *
	 * @since 8.1.0
	 */
	public function exists(): bool;

	/**
	 * Check if the avatar of a user is a custom uploaded one
	 *
	 * @since 14.0.0
	 */
	public function isCustomAvatar(): bool;

	/**
	 * Sets the users avatar
	 *
	 * @param \OCP\IImage|resource|string $data An image object, imagedata or path to set a new avatar
	 * @throws \Exception if the provided file is not a jpg or png image
	 * @throws \Exception if the provided image is not valid
	 * @throws \OC\NotSquareException if the image is not square
	 * @since 6.0.0
	 */
	public function set($data): void;

	/**
	 * Remove the user's avatar
	 *
	 * @param bool $silent Whether removing the avatar should trigger a change
	 * @since 6.0.0
	 */
	public function remove(bool $silent = false): void;

	/**
	 * Get the file of the avatar
	 *
	 * @param int $size The desired image size. -1 can be used to not scale the image
	 * @param bool $darkTheme Should the generated avatar be dark themed
	 * @throws NotFoundException
	 * @since 9.0.0
	 */
	public function getFile(int $size, bool $darkTheme = false): ISimpleFile;

	/**
	 * Get the avatar background color
	 *
	 * @since 14.0.0
	 */
	public function avatarBackgroundColor(string $hash): Color;

	/**
	 * Updates the display name if changed.
	 *
	 * @param string $feature The changed feature
	 * @param mixed $oldValue The previous value
	 * @param mixed $newValue The new value
	 * @since 13.0.0
	 */
	public function userChanged(string $feature, $oldValue, $newValue): void;
}
