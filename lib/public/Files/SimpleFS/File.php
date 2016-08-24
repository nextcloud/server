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

use OCP\Files\NotPermittedException;

interface File {

	/**
	 * Get the name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get the size in bytes
	 *
	 * @return int
	 */
	public function getSize();

	/**
	 * Get the ETag
	 *
	 * @return string
	 */
	public function getETag();

	/**
	 * Get the last modification time
	 *
	 * @return int
	 */
	public function getMTime();

	/**
	 * Get the content
	 *
	 * @return string
	 */
	public function getContent();

	/**
	 * Overwrite the file
	 *
	 * @param string $data
	 * @throws NotPermittedException
	 */
	public function putContent($data);

	/**
	 * Delete the file
	 *
	 * @throws NotPermittedException
	 */
	public function delete();

	/**
	 * Get the MimeType
	 *
	 * @return string
	 */
	public function getMimeType();
}
