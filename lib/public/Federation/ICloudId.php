<?php
/**
 * @copyright Copyright (c) 2017, Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\Federation;

/**
 * Parsed federated cloud id
 *
 * @since 12.0.0
 */
interface ICloudId {
	/**
	 * The remote cloud id
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getId();

	/**
	 * Get a clean representation of the cloud id for display
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getDisplayId();

	/**
	 * The username on the remote server
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getUser();

	/**
	 * The base address of the remote server
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getRemote();
}
