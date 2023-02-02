<?php
/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCP\Files\SimpleFS;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

/**
 * Interface ISimpleFolder
 *
 * @since 11.0.0
 */
interface ISimpleFolder {
	/**
	 * Get all the files in a folder
	 *
	 * @return ISimpleFile[]
	 * @since 11.0.0
	 */
	public function getDirectoryListing(): array;

	/**
	 * Check if a file with $name exists
	 *
	 * @param string $name
	 * @return bool
	 * @since 11.0.0
	 */
	public function fileExists(string $name): bool;

	/**
	 * Get the file named $name from the folder
	 *
	 * @throws NotFoundException
	 * @since 11.0.0
	 */
	public function getFile(string $name): ISimpleFile;

	/**
	 * Creates a new file with $name in the folder
	 *
	 * @param string|resource|null $content @since 19.0.0
	 * @throws NotPermittedException
	 * @since 11.0.0
	 */
	public function newFile(string $name, $content = null): ISimpleFile;

	/**
	 * Remove the folder and all the files in it
	 *
	 * @throws NotPermittedException
	 * @since 11.0.0
	 */
	public function delete(): void;

	/**
	 * Get the folder name
	 *
	 * @since 11.0.0
	 */
	public function getName(): string;

	/**
	 * Get the folder named $name from the current folder
	 *
	 * @throws NotFoundException
	 * @since 25.0.0
	 */
	public function getFolder(string $name): ISimpleFolder;

	/**
	 * Creates a new folder with $name in the current folder
	 *
	 * @param string|resource|null $content @since 19.0.0
	 * @throws NotPermittedException
	 * @since 25.0.0
	 */
	public function newFolder(string $path): ISimpleFolder;
}
