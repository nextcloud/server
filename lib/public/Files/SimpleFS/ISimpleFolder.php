<?php
/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Files\SimpleFS;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

/**
 * Interface ISimpleFolder
 *
 * @package OCP\Files\SimpleFS
 * @since 9.2.0
 * @internal This interface is experimental and might change for NC12
 */
interface ISimpleFolder {
	/**
	 * Get all the files in a folder
	 *
	 * @return ISimpleFile[]
	 * @since 9.2.0
	 */
	public function getDirectoryListing();

	/**
	 * Check if a file with $name exists
	 *
	 * @param string $name
	 * @return bool
	 * @since 9.2.0
	 */
	public function fileExists($name);

	/**
	 * Get the file named $name from the folder
	 *
	 * @param string $name
	 * @return ISimpleFile
	 * @throws NotFoundException
	 * @since 9.2.0
	 */
	public function getFile($name);

	/**
	 * Creates a new file with $name in the folder
	 *
	 * @param string $name
	 * @return ISimpleFile
	 * @throws NotPermittedException
	 * @since 9.2.0
	 */
	public function newFile($name);

	/**
	 * Remove the folder and all the files in it
	 *
	 * @throws NotPermittedException
	 * @since 9.2.0
	 */
	public function delete();

	/**
	 * Get the folder name
	 *
	 * @return string
	 * @since 9.2.0
	 */
	public function getName();
}
