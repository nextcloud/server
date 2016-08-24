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

/**
 * Interface ISimpleRoot
 *
 * @package OCP\Files\SimpleFS
 * @since 9.2.0
 * @internal This interface is experimental and might change for NC12
 */
interface ISimpleRoot {
	/**
	 * Get the folder with name $name
	 *
	 * @param string $name
	 * @return ISimpleFolder
	 * @throws NotFoundException
	 * @since 9.2.0
	 */
	public function getFolder($name);

	/**
	 * Get all the Folders
	 *
	 * @return ISimpleFolder[]
	 * @since 9.2.0
	 */
	public function getDirectoryListing();

	/**
	 * Create a new folder named $name
	 *
	 * @param string $name
	 * @return ISimpleFolder
	 * @since 9.2.0
	 */
	public function newFolder($name);
}
