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

interface Root {
	/**
	 * Get the folder with name $name
	 *
	 * @param string $name
	 * @return Folder
	 * @throws NotFoundException
	 */
	public function getFolder($name);

	/**
	 * Get all the Folders
	 *
	 * @return Folder[]
	 */
	public function getDirectoryListing();

	/**
	 * Create a new folder named $name
	 *
	 * @param string $name
	 * @return Folder
	 */
	public function newFolder($name);
}
